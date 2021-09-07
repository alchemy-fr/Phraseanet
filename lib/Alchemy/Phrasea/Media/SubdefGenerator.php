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
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationFailedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreationEvent;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaAlchemyst\Exception\FileNotFoundException;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;
use MediaVorus\Exception\FileNotFoundException as MediaVorusFileNotFoundException;
use MediaVorus\MediaVorus;
use Neutron\TemporaryFilesystem\Manager;
use Psr\Log\LoggerInterface;
use Unoconv\Exception\ExceptionInterface as UnoconvException;
use Unoconv\Exception\RuntimeException;
use Unoconv\Unoconv;

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
        $this->tmpDirectory = $this->app['conf']->get(['main', 'storage', 'worker_tmp_files']);;
    }

    //************************ dirty hack to trace "wip" document  file
    public function generateSubdefs(\record_adapter $record, array $wanted_subdefs = null)
    {
        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into generateSubdefs(%s.%s, %s)", $record->getDataboxId(), $record->getRecordId(), $wanted_subdefs ? ('[' . join(', ', $wanted_subdefs) . ']') : 'null')
        ), FILE_APPEND | LOCK_EX);

        for ($ntry = 1; $ntry <= 4; $ntry++) {

            $hd = $record->get_hd_file();
            if($hd) {
                clearstatcache($hd->getRealPath());
                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("hd=\"%s\" (size=%s) for %s.%s", $hd->getRealPath(), filesize($hd->getRealPath()), $record->getDataboxId(), $record->getRecordId())
                ), FILE_APPEND | LOCK_EX);

                $this->_generateSubdefs($record, $wanted_subdefs);  // void

                break;
            }
            file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("!!! try %s/4 : record->get_hd_file() returned null for %s.%s, retry in 2 sec.", $ntry, $record->getDataboxId(), $record->getRecordId())
            ), FILE_APPEND | LOCK_EX);
            sleep(2);

        }

        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from generateSubdefs(...)")
        ), FILE_APPEND | LOCK_EX);

    }



    private function _generateSubdefs(\record_adapter $record, array $wanted_subdefs = null)
    {
        $hd = $record->get_hd_file();   // should be ok since checked before, but who knows

        if(!$hd) {
            file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("!!! record->get_hd_file() returned null for %s.%s", $record->getDataboxId(), $record->getRecordId())
            ), FILE_APPEND | LOCK_EX);
        }
        else {
            file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("hd=\"%s\" (size=%s) for %s.%s", $hd->getRealPath(), filesize($hd->getRealPath()), $record->getDataboxId(), $record->getRecordId())
            ), FILE_APPEND | LOCK_EX);
        }


        if ($hd !== null && $hd->getMimeType() == "application/x-indesign") {
            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("application/x-indesign for %s.%s", $record->getDataboxId(), $record->getRecordId())
            ), FILE_APPEND | LOCK_EX);

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
                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("deleting previous subdef \"%s\" (file=\"%s\") for %s.%s", $subdefname, $record->get_subdef($subdefname)->getRealPath(), $record->getDataboxId(), $record->getRecordId())
                ), FILE_APPEND | LOCK_EX);

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

            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("generating subdef \"%s\" to \"%s\" for %s.%s", $subdefname, $pathdest, $record->getDataboxId(), $record->getRecordId())
            ), FILE_APPEND | LOCK_EX);

            $this->logger->info(sprintf('Generating subdef %s to %s', $subdefname, $pathdest));
            $this->generateSubdef($record, $subdef, $pathdest);

            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("generated subdef \"%s\" to \"%s\" for %s.%s", $subdefname, $pathdest, $record->getDataboxId(), $record->getRecordId())
            ), FILE_APPEND | LOCK_EX);

            if ($this->filesystem->exists($pathdest)) {
                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("file \"%s\" for %s.%s.%s exists, ok", $pathdest, $record->getDataboxId(), $record->getRecordId(), $subdefname)
                ), FILE_APPEND | LOCK_EX);

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
                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("file \"%s\" for %s.%s.%s does not exists, bad", $pathdest, $record->getDataboxId(), $record->getRecordId(), $subdefname)
                ), FILE_APPEND | LOCK_EX);

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

        // if we created subdef one by one
        if (count($wanted_subdefs) == 1) {
            $mediaSubdefRepository = $this->getMediaSubdefRepository($record->getDataboxId());
            $mediaSubdefs = $mediaSubdefRepository->findByRecordIdsAndNames([$record->getRecordId()]);
            $medias = [];
            foreach ($mediaSubdefs as $subdef) {
                try {
                    $medias[$subdef->get_name()] = $this->mediavorus->guess($subdef->getRealPath());
                } catch (MediaVorusFileNotFoundException $e) {

                }
            }

            $this->dispatch(
                RecordEvents::SUB_DEFINITIONS_CREATED,
                new SubDefinitionsCreatedEvent(
                    $record,
                    $medias
                )
            );
        }
        else {
            $this->dispatch(
                RecordEvents::SUB_DEFINITIONS_CREATED,
                new SubDefinitionsCreatedEvent(
                    $record,
                    $mediaCreated
                )
            );
        }

    }

    /**
     * set a logger to use
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * to get the logger
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /** ****** dirty hack to retry to create subdef if failed ************ */
    private function generateSubdef(\record_adapter $record, \databox_subdef $subdef_class, $pathdest)
    {
        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into generateSubdef(%s.%s, %s, \"%s\")", $record->getDataboxId(), $record->getRecordId(), $subdef_class->get_name(), $pathdest)
        ), FILE_APPEND | LOCK_EX);

        for ($ntry = 1; $ntry <= 4; $ntry++) {
            $this->_generateSubdef($record, $subdef_class, $pathdest);
            if(file_exists($pathdest)) {
                break;
            }
            else {
                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("!!! try %s/4 : subdef file \"%s\" does not exists, retry in 2 sec.", $ntry, $pathdest)
                ), FILE_APPEND | LOCK_EX);
                sleep(2);
            }
        }

        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from generateSubdef(...)")
        ), FILE_APPEND | LOCK_EX);
    }

    private function _generateSubdef(\record_adapter $record, \databox_subdef $subdef_class, $pathdest)
    {
        $start = microtime(true);
        $destFile = null;

        try {
            if (null === $record->get_hd_file()) {
                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("ho hd, aborting")
                ), FILE_APPEND | LOCK_EX);

                $this->logger->info('No HD file found, aborting');

                return;
            }

            if($subdef_class->getSpecs() instanceof Video && !empty($this->tmpDirectory)){
                $destFile = $pathdest;
                $pathdest = $this->filesystem->generateTemporaryFfmpegPathname($record, $subdef_class, $this->tmpDirectory);
            }

            if (isset($this->tmpFilePath) && $subdef_class->getSpecs() instanceof Image) {

                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("calling alchemyst->turnInto from \"%s\" (size=%s) to \"%s\"", $this->tmpFilePath, filesize($this->tmpFilePath), $pathdest)
                ), FILE_APPEND | LOCK_EX);

                $this->alchemyst->turnInto($this->tmpFilePath, $pathdest, $subdef_class->getSpecs());

            } elseif ($subdef_class->getSpecs() instanceof PdfSpecification){

                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("calling generatePdfSubdef from \"%s\" (size=%s) to \"%s\"", $record->get_hd_file()->getPathname(), filesize($record->get_hd_file()->getPathname()), $pathdest)
                ), FILE_APPEND | LOCK_EX);

                $this->generatePdfSubdef($record->get_hd_file()->getPathname(), $pathdest);

            } else {

                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("calling alchemyst->turnInto from \"%s\" (size=%s) to \"%s\"", $record->get_hd_file()->getPathname(), filesize($record->get_hd_file()->getPathname()), $pathdest)
                ), FILE_APPEND | LOCK_EX);

                $this->alchemyst->turnInto($record->get_hd_file()->getPathname(), $pathdest, $subdef_class->getSpecs());

            }
            if(file_exists($pathdest)) {
                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("returned from alchemyst->turnInto() or generatePdfSubdef() with \"%s\" (size=%s)", $pathdest, filesize($pathdest))
                ), FILE_APPEND | LOCK_EX);
            }
            else {
                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("!!! returned from alchemyst->turnInto() or generatePdfSubdef(), \"%s\" does not exists", $pathdest)
                ), FILE_APPEND | LOCK_EX);
            }


            if($destFile){
                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("copying from \"%s\" (size=%s) to \"%s\"", $pathdest, filesize($pathdest), $destFile)
                ), FILE_APPEND | LOCK_EX);

                $this->filesystem->copy($pathdest, $destFile);

                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("copied from \"%s\" to \"%s\" (size=%s)", $pathdest, $destFile, filesize($destFile))
                ), FILE_APPEND | LOCK_EX);

                file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("removing \"%s\"", $pathdest)
                ), FILE_APPEND | LOCK_EX);

                $this->app['filesystem']->remove($pathdest);
            }

        }
        catch (MediaAlchemystException $e) {

            file_put_contents(dirname(__FILE__) . '/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("!!! MediaAlchemystException \"%s\"", $e->getMessage())
            ), FILE_APPEND | LOCK_EX);


            $start = 0;
            $this->logger->error(sprintf('Subdef generation failed for record %d with message %s', $record->getRecordId(), $e->getMessage()));
        }

        $stop = microtime(true);
        if($start){
            $duration = $stop - $start;

            $originFileSize = $this->sizeHumanReadable($record->get_hd_file()->getSize());

            if($destFile){
                $generatedFileSize = $this->sizeHumanReadable(filesize($destFile));
            }
            else {
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

    /**
     * @param $databoxId
     *
     * @return MediaSubdefRepository|Object
     */
    private function getMediaSubdefRepository($databoxId)
    {
        return $this->app['provider.repo.media_subdef']->getRepositoryForDatabox($databoxId);
    }
}
