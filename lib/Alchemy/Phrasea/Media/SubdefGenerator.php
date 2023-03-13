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
use Alchemy\Phrasea\Media\Subdef\OptionType\Boolean;
use Alchemy\Phrasea\Media\Subdef\OptionType\Text;
use Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification;
use databox_subdef;
use Exception;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Imagine;
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

        if(!is_null($hd = $record->get_hd_file())) {
            $hd = $hd->getRealPath();

            clearstatcache(true, $hd);
        }

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
        } else {
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

    private function generateSubdef(\record_adapter $record, databox_subdef $subdef_class, $pathdest)
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

                file_put_contents(dirname(__FILE__).'/../../../../logs/subdefgenerator.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf(
                        "into generateSubdef for image subdef %s", $subdef_class->get_name()
                    )
                ), FILE_APPEND | LOCK_EX);

                $this->alchemyst->turnInto($this->tmpFilePath, $pathdest, $subdef_class->getSpecs());

            }
            elseif ($subdef_class->getSpecs() instanceof PdfSpecification){

                $this->generatePdfSubdef($record->get_hd_file()->getPathname(), $pathdest);

            }
            else {

                $this->alchemyst->turnInto($record->get_hd_file()->getPathname(), $pathdest, $subdef_class->getSpecs());

            }

            if($destFile) {
                // the file was built elsewhere, we copy it to original destination
                $this->filesystem->copy($pathdest, $destFile);
                $this->app['filesystem']->remove($pathdest);
                $pathdest = $destFile;
            }

        }
        catch (MediaAlchemystException $e) {
            $start = 0;
            $this->logger->error(sprintf('Subdef generation failed for record %d with message %s', $record->getRecordId(), $e->getMessage()));
        }

        if($start) {

            // the subdef was done

            // watermark ?
            if($subdef_class->getSpecs() instanceof Image) {
                /** @var Subdef\Image $image */
                $image = $subdef_class->getSubdefType();
                /** @var Boolean $wm */
                $wm = $image->getOption(Subdef\Image::OPTION_WATERMARK);
                if($wm->getValue() === 'yes') {     // bc to "text" mode
                    // we must watermark the file
                    $wm_text = null;
                    $wm_image = null;

                    /** @var Text $opt */

                    $opt = $image->getOption(Subdef\Image::OPTION_WATERMARKTEXT);
                    if($opt && ($t = trim($opt->getValue())) !== '') {
                        $wm_text = $t;
                    }

                    $opt = $image->getOption(Subdef\Image::OPTION_WATERMARKRID);
                    if($opt && ($rid = trim($opt->getValue())) !== '') {
                        try {
                            $wm_image = $subdef_class->getDatabox()->get_record($rid)->get_subdef('document')->getRealPath();
                        }
                        catch (\Exception $e) {
                            $this->logger->error(sprintf('Getting wm image (record %d) failed with message %s', $rid, $e->getMessage()));
                        }
                    }

                    if(!is_null($wm_text) || !is_null($wm_image)) {
                        $this->wartermarkImageFile($pathdest, $wm_text, $wm_image);
                    }
                }
            }

            $duration = microtime(true) - $start;

            $originFileSize = $this->sizeHumanReadable($record->get_hd_file()->getSize());
            $generatedFileSize = $this->sizeHumanReadable(filesize($pathdest));

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

    /**
     * @param string $filepath
     * @param string|null $watermarkText
     * @param string|null $watermarkImage
     */
    private function wartermarkImageFile(string $filepath, $watermarkText, $watermarkImage)
    {
        static $palette;

        /** @var Imagine $imagine */
        $imagine = $this->getImagine();

        $in_image = $imagine->open($filepath);
        $in_size = $in_image->getSize();
        $in_w = $in_size->getWidth();
        $in_h = $in_size->getHeight();

        $in_image_changed = false;

        if ($watermarkImage !== null && file_exists($watermarkImage)) {
            $wm_image = $imagine->open($watermarkImage);
            $wm_size = $wm_image->getSize();
            $wm_w = $wm_size->getWidth();
            $wm_h = $wm_size->getHeight();

            if (($wm_w / $wm_h) > ($in_w / $in_h)) {
                $wm_size = $wm_size->widen($in_w);
            }
            else {
                $wm_size = $wm_size->heighten($in_h);
            }
            $wm_image->resize($wm_size);

            $in_image->paste($wm_image, new Point(($in_w - $wm_size->getWidth()) >> 1, ($in_h - $wm_size->getHeight()) >> 1));

            $in_image_changed = true;
        }

        if($watermarkText !== null) {
            if (null === $palette) {
                $palette = new RGB();
            }

            $draw = $in_image->draw();
            $black = $palette->color("000000");
            $white = $palette->color("FFFFFF");
            $draw->line(new Point(0, 1), new Point($in_w - 2, $in_h - 1), $black);
            $draw->line(new Point(1, 0), new Point($in_w - 1, $in_h - 2), $white);
            $draw->line(new Point(0, $in_h - 2), new Point($in_w - 2, 0), $black);
            $draw->line(new Point(1, $in_h - 1), new Point($in_w - 1, 1), $white);

            if ($watermarkText) {
                $fsize = max(8, (int)(max($in_w, $in_h) / 30));
                $fonts = [
                    $imagine->font(__DIR__ . '/../../../../resources/Fonts/arial.ttf', $fsize, $black),
                    $imagine->font(__DIR__ . '/../../../../resources/Fonts/arial.ttf', $fsize, $white)
                ];
                $testbox = $fonts[0]->box($watermarkText, 0);
                $tx_w = min($in_w, $testbox->getWidth());
                $tx_h = min($in_h, $testbox->getHeight());

                $x0 = max(1, ($in_w - $tx_w) >> 1);
                $y0 = max(1, ($in_h - $tx_h) >> 1);
                for ($i = 0; $i <= 1; $i++) {
                    $x = max(1, ($in_w >> 2) - ($tx_w >> 1));
                    $draw->text($watermarkText, $fonts[$i], new Point($x - $i, $y0 - $i));
                    $x = max(1, $in_w - $x - $tx_w);
                    $draw->text($watermarkText, $fonts[$i], new Point($x - $i, $y0 - $i));

                    $y = max(1, ($in_h >> 2) - ($tx_h >> 1));
                    $draw->text($watermarkText, $fonts[$i], new Point($x0 - $i, $y - $i));
                    $y = max(1, $in_h - $y - $tx_h);
                    $draw->text($watermarkText, $fonts[$i], new Point($x0 - $i, $y - $i));
                }
            }
            $in_image_changed = true;
        }

        if($in_image_changed) {
            $in_image->save($filepath);
        }
    }

    /**
     * Used only by api V3 subdef generator service
     *
     * @param $pathSrc
     * @param databox_subdef $subdef_class
     * @param $pathdest
     */
    public function generateSubdefFromFile($pathSrc, databox_subdef $subdef_class, $pathdest)
    {
        $start = microtime(true);
        $destFile = null;

        try {
            if($subdef_class->getSpecs() instanceof Video && !empty($this->tmpDirectory)){
                // a video must be generated on worker tmp (from conf) : change pathdest
                $destFile = $pathdest;

                $ffmpegDir = \p4string::addEndSlash($this->tmpDirectory) . "ffmpeg/";
                if(!is_dir($ffmpegDir)){
                    $this->filesystem->mkdir($ffmpegDir);
                }
                $tmpname = str_replace('.', '_', (string)$start) .
                    '_' . $subdef_class->get_name() .
                    '.' . $this->filesystem->getExtensionFromSpec($subdef_class->getSpecs());

                $pathdest = $ffmpegDir . $tmpname;
            }

            if (isset($this->tmpFilePath) && $subdef_class->getSpecs() instanceof Image) {

                $this->alchemyst->turnInto($this->tmpFilePath, $pathdest, $subdef_class->getSpecs());

            }
            elseif ($subdef_class->getSpecs() instanceof PdfSpecification){

                $this->generatePdfSubdef($pathSrc, $pathdest);

            }
            else {

                $this->alchemyst->turnInto($pathSrc, $pathdest, $subdef_class->getSpecs());

            }

            if($destFile){
                // the video subdef was generated on tmp, copy it to original dest
                $this->filesystem->copy($pathdest, $destFile);
                $this->app['filesystem']->remove($pathdest);
            }

        }
        catch (Exception $e) {
            $this->logger->error(sprintf('Subdef generation failed with message %s', $e->getMessage()));
        }

        // watermark ?
        if($subdef_class->getSpecs() instanceof Image) {
            /** @var Subdef\Image $image */
            $image = $subdef_class->getSubdefType();
            /** @var Boolean $wm */
            $wm = $image->getOption(Subdef\Image::OPTION_WATERMARK);

            if($wm->getValue() === 'yes') {     // bc to "text" mode
                // we must watermark the file
                $wm_text = null;
                $wm_image = null;

                /** @var Text $opt */

                $opt = $image->getOption(Subdef\Image::OPTION_WATERMARKTEXT);
                if($opt && ($t = trim($opt->getValue())) !== '') {
                    $wm_text = $t;
                }

                $opt = $image->getOption(Subdef\Image::OPTION_WATERMARKRID);
                if($opt && ($rid = trim($opt->getValue())) !== '') {
                    try {
                        $wm_image = $subdef_class->getDatabox()->get_record($rid)->get_subdef('document')->getRealPath();
                    }
                    catch (\Exception $e) {
                        $this->logger->error(sprintf('Getting wm image (record %d) failed with message %s', $rid, $e->getMessage()));
                    }
                }

                if(!is_null($wm_text) || !is_null($wm_image)) {
                    $this->wartermarkImageFile($pathdest, $wm_text, $wm_image);
                }
            }
        }

        $duration = microtime(true) - $start;

        $originFileSize = $this->sizeHumanReadable(filesize($pathSrc));

        if($destFile){
            $generatedFileSize = $this->sizeHumanReadable(filesize($destFile));
        }else{
            $generatedFileSize = $this->sizeHumanReadable(filesize($pathdest));
        }

        $this->logger->info(sprintf('*** Generated *** %s , duration=%s / source size=%s / %s size=%s',
                $subdef_class->get_name(),
                date('H:i:s', mktime(0,0, $duration)),
                $originFileSize,
                $subdef_class->get_name(),
                $generatedFileSize
            )
        );
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
        } catch (Exception $e) {
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

    /**
     * @return ImagineInterface $imagine
     */
    private function getImagine()
    {
        return $this->app['imagine'];
    }

}
