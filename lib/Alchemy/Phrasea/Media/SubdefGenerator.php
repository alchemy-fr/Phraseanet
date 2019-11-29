<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationFailedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;
use MediaVorus\MediaVorus;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use Neutron\TemporaryFilesystem\Manager;
use Psr\Log\LoggerInterface;
use Unoconv\Exception\ExceptionInterface as UnoconvException;
use Unoconv\Exception\RuntimeException;
use Unoconv\Unoconv;
use MediaVorus\Exception\FileNotFoundException as MediaVorusFileNotFoundException;
use MediaAlchemyst\Exception\FileNotFoundException;

class SubdefGenerator
{
    use DispatcherAware;

    private $app;
    private $alchemyst;
    private $filesystem;
    /**
     * @var LoggerInterface
     */
    private $logger;
    private $mediavorus;
    private $tmpFilePath;
    private $tmpFilesystem;
    private $tmpDirectory;

    public function __construct(Application $app, Alchemyst $alchemyst, FilesystemService $filesystem, MediaVorus $mediavorus, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->alchemyst = $alchemyst;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->mediavorus = $mediavorus;
        $this->tmpDirectory = $this->app['conf']->get(['main', 'storage', 'tmp_files']);;
    }

    public function generateSubdefs(\record_adapter $record, array $wanted_subdefs = null)
    {
        if ($record->get_hd_file() !== null && $record->get_hd_file()->getMimeType() == "application/x-indesign") {
            $mediaSource = $this->mediavorus->guess($record->get_hd_file()->getPathname());
            $metadatas = $mediaSource->getMetadatas();

            if ($metadatas->containsKey('XMP-xmp:PageImage')) {
                if(!isset($this->tmpFilesystem)){
                    $this->tmpFilesystem = Manager::create();
                }
                $tmpDir = $this->tmpFilesystem->createTemporaryDirectory(0777, 500);

                $files = $this->app['exiftool.preview-extractor']->extract($record->get_hd_file()->getPathname(), $tmpDir);

                $selected = null;
                $size = null;

                foreach ($files as $file) {
                    if ($file->isDir() || $file->isDot()) {
                        continue;
                    }

                    if (is_null($selected) || $file->getSize() > $size) {
                        $selected = $file->getPathname();
                        $size = $file->getSize();
                    }
                }

                if ($selected) {
                    $this->tmpFilePath =  $selected;
                }
            }
        }

        if (null === $subdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())) {
            $this->logger->info(sprintf('Nothing to do for %s', $record->getType()));
            $subdefs = [];
        }

        $this->dispatch(
            RecordEvents::SUB_DEFINITIONS_CREATION,
            new SubDefinitionsCreationEvent(
                $record,
                $wanted_subdefs
            )
        );

        $mediaCreated = [];
        foreach ($subdefs as $subdef) {
            $subdefname = $subdef->get_name();

            if ($wanted_subdefs && !in_array($subdefname, $wanted_subdefs)) {
                continue;
            }

            $pathdest = null;

            if ($record->has_subdef($subdefname) && $record->get_subdef($subdefname)->is_physically_present()) {
                $pathdest = $record->get_subdef($subdefname)->getRealPath();
                $record->get_subdef($subdefname)->remove_file();
                $this->logger->info(sprintf('Removed old file for %s', $subdefname));
                $record->clearSubdefCache($subdefname);
            }

            $pathdest = $this->filesystem->generateSubdefPathname($record, $subdef, $pathdest);

            $this->dispatch(
                RecordEvents::SUB_DEFINITION_CREATION,
                new SubDefinitionCreationEvent(
                    $record,
                    $subdefname
                )
            );

            $this->logger->info(sprintf('Generating subdef %s to %s', $subdefname, $pathdest));
            $this->generateSubdef($record, $subdef, $pathdest);

            if ($this->filesystem->exists($pathdest)) {
                $media = $this->mediavorus->guess($pathdest);

                \media_subdef::create($this->app, $record, $subdef->get_name(), $media);

                $this->dispatch(
                    RecordEvents::SUB_DEFINITION_CREATED,
                    new SubDefinitionCreatedEvent(
                        $record,
                        $subdefname,
                        $mediaCreated[$subdefname] = $media
                    )
                );
            }
            else {
                $this->dispatch(
                    RecordEvents::SUB_DEFINITION_CREATION_FAILED,
                    new SubDefinitionCreationFailedEvent(
                        $record,
                        $subdefname
                    )
                );
            }

            $record->clearSubdefCache($subdefname);
        }

        if(isset($this->tmpFilesystem)){
            $this->tmpFilesystem->clean();
        }
        if(isset($this->tmpFilePath)){
            unset($this->tmpFilePath);
        }

        $this->dispatch(
            RecordEvents::SUB_DEFINITIONS_CREATED,
            new SubDefinitionsCreatedEvent(
                $record,
                $mediaCreated
            )
        );
    }

    private function generateSubdef(\record_adapter $record, \databox_subdef $subdef_class, $pathdest)
    {
        $start = microtime(true);
        $destFile = null;

        try {
            if (null === $record->get_hd_file()) {
                $this->logger->info('No HD file found, aborting');

                return;
            }

            if($subdef_class->getSpecs() instanceof Video && !empty($this->tmpDirectory)){
                $destFile = $pathdest;
                $pathdest = $this->filesystem->generateTemporaryFfmpegPathname($record, $subdef_class, $this->tmpDirectory);
            }

            if (isset($this->tmpFilePath) && $subdef_class->getSpecs() instanceof Image) {

                $this->alchemyst->turnInto($this->tmpFilePath, $pathdest, $subdef_class->getSpecs());

            } elseif ($subdef_class->getSpecs() instanceof PdfSpecification){

                $this->generatePdfSubdef($record->get_hd_file()->getPathname(), $pathdest);

            } else {

                $this->alchemyst->turnInto($record->get_hd_file()->getPathname(), $pathdest, $subdef_class->getSpecs());

            }

            if($destFile){
                $this->filesystem->copy($pathdest, $destFile);
                $this->app['filesystem']->remove($pathdest);
            }

        } catch (MediaAlchemystException $e) {
            $start = 0;
            $this->logger->error(sprintf('Subdef generation failed for record %d with message %s', $record->getRecordId(), $e->getMessage()));
        }

        $stop = microtime(true);
        if($start){
            $duration = $stop - $start;

            $originFileSize = $this->sizeHumanReadable($record->get_hd_file()->getSize());

            if($destFile){
                $generatedFileSize = $this->sizeHumanReadable(filesize($destFile));
            }else{
                $generatedFileSize = $this->sizeHumanReadable(filesize($pathdest));
            }

            $this->logger->info(sprintf('*** Generated *** %s , duration=%s / source size=%s / %s size=%s / sbasid=%s / databox=%s / recordid=%s',
                    $subdef_class->get_name(),
                    date('H:i:s', mktime(0,0, $duration)),
                    $originFileSize,
                    $subdef_class->get_name(),
                    $generatedFileSize,
                    $record->getDatabox()->get_sbas_id(),
                    $record->getDatabox()->get_dbname(),
                    $record->getRecordId()
                )
            );
        }

    }

    private function generatePdfSubdef($source, $pathdest)
    {
        try {
            $mediafile = $this->app['mediavorus']->guess($source);
        } catch (MediaVorusFileNotFoundException $e) {
            throw new FileNotFoundException(sprintf('File %s not found', $source));
        }

        try {
            if ($mediafile->getFile()->getMimeType() != 'application/pdf') {
                $this->app['unoconv']->transcode(
                    $mediafile->getFile()->getPathname(), Unoconv::FORMAT_PDF, $pathdest
                );

            } else {
                copy($mediafile->getFile()->getPathname(), $pathdest);
            }
        } catch (UnoconvException $e) {
            throw new RuntimeException('Unable to transmute document to pdf due to Unoconv', null, $e);
        } catch (\Exception $e) {
            throw $e;
        }

    }

    private function sizeHumanReadable($bytes) {
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), [0,0,2,2,3][$i]).['B','kB','MB','GB'][$i];
    }
}
