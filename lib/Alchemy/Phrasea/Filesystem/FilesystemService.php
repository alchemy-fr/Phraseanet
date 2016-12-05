<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Filesystem;

use Alchemy\Phrasea\Model\RecordInterface;
use MediaAlchemyst\Specification\SpecificationInterface;

class FilesystemService
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $filesystem;

    public function __construct(\Symfony\Component\Filesystem\Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string $repository_path
     * @return string
     */
    public function directorySpread($repository_path)
    {
        $repository_path = \p4string::addEndSlash($repository_path);

        $timestamp = strtotime(date('Y-m-d'));
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);

        $comp = $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR . $day . DIRECTORY_SEPARATOR;

        $n = 0;
        do {
            $pathout = sprintf('%s%s%05d', $repository_path, $comp, $n++);
        } while (is_dir($pathout) && iterator_count(new \DirectoryIterator($pathout)) > 100);

        $this->filesystem->mkdir($pathout, 0750);

        return $pathout . DIRECTORY_SEPARATOR;
    }

    public function exists($path)
    {
        return $this->filesystem->exists($path);
    }

    public function generateSubdefPathname(\record_adapter $record, \databox_subdef $subdef, $oldVersion)
    {
        if ($oldVersion) {
            $pathdest = \p4string::addEndSlash(pathinfo($oldVersion, PATHINFO_DIRNAME));
        } else {
            $pathdest = $this->directorySpread($subdef->get_path());
        }

        return $pathdest . $this->generateSubdefFilename($record, $subdef);
    }

    /**
     * @param RecordInterface $record
     * @param string|\SplFileInfo $source
     * @return string
     */
    public function generateDocumentFilename(RecordInterface $record, $source)
    {
        if (!$source instanceof \SplFileInfo) {
            $source = new \SplFileInfo($source);
        }

        return $record->getRecordId() . '_document.' . strtolower($source->getExtension());
    }

    /**
     * @param \record_adapter $record
     * @param \databox_subdef $subdef
     * @param string $marker
     * @return string
     */
    public function generateSubdefFilename(\record_adapter $record, \databox_subdef $subdef, $marker = '')
    {
        return $record->getRecordId() . '_' . $marker . $subdef->get_name() . '.' . $this->getExtensionFromSpec($subdef->getSpecs());
    }

    public function generateSubdefSubstitutionPathname(\record_adapter $record, \databox_subdef $subdef)
    {
        $pathdest = $this->directorySpread($subdef->get_path());

        return $pathdest . $this->generateSubdefFilename($record, $subdef, '0_');
    }

    /**
     * @param \databox $databox
     * @return string
     */
    public function generateDataboxDocumentBasePath(\databox $databox)
    {
        $baseprefs = $databox->get_sxml_structure();

        return $this->directorySpread(\p4string::addEndSlash((string)($baseprefs->path)));
    }

    /**
     * Write Media source file with given filename
     *
     * @param \databox $databox
     * @param string $source
     * @param string $filename
     * @return string
     */
    public function writeMediaSourceFile(\databox $databox, $source, $filename)
    {
        $realPath = $this->generateDataboxDocumentBasePath($databox) . $filename;

        $this->filesystem->copy($source, $realPath, true);
        $this->filesystem->chmod($realPath, 0760);

        return $realPath;
    }

    /**
     * Copy file from source to target
     *
     * @param string $source
     * @param string $target
     */
    public function copy($source, $target)
    {
        $this->filesystem->copy($source, $target, true);
    }

    public function chmod($files, $mode, $umask = 0000, $recursive = false)
    {
        $this->filesystem->chmod($files, $mode, $umask, $recursive);
    }

    /**
     * Get the extension from MediaAlchemyst specs
     *
     * @param SpecificationInterface $spec
     *
     * @return string
     */
    private function getExtensionFromSpec(SpecificationInterface $spec)
    {
        switch ($spec->getType()) {
            case SpecificationInterface::TYPE_IMAGE:
                return $this->getExtensionFromImageCodec($spec->getImageCodec());
            case SpecificationInterface::TYPE_ANIMATION:
                return 'gif';
            case SpecificationInterface::TYPE_AUDIO:
                return $this->getExtensionFromAudioCodec($spec->getAudioCodec());
            case SpecificationInterface::TYPE_VIDEO:
                return $this->getExtensionFromVideoCodec($spec->getVideoCodec());
            case SpecificationInterface::TYPE_SWF:
                return 'swf';
        }

        return null;
    }

    /**
     * Get the extension from audiocodec
     *
     * @param string $audioCodec
     *
     * @return string
     */
    private function getExtensionFromAudioCodec($audioCodec)
    {
        switch ($audioCodec) {
            case 'flac':
                return 'flac';
            case 'libvorbis':
                return 'ogg';
            case 'libmp3lame':
                return 'mp3';
        }

        return null;
    }

    /**
     * Get the extension from imageCodec
     *
     * @param  string $imageCodec
     *
     * @return string
     */
    protected function getExtensionFromImageCodec($imageCodec)
    {
        switch ($imageCodec) {
            case 'tiff':
                return 'tif';
            case 'jpeg':
                return 'jpg';
            case 'png':
                return 'png';
        }

        return null;
    }

    /**
     * Get the extension from videocodec
     *
     * @param string $videoCodec
     *
     * @return string
     */
    private function getExtensionFromVideoCodec($videoCodec)
    {
        switch ($videoCodec) {
            case 'libtheora':
                return 'ogv';
            case 'libvpx':
                return 'webm';
            case 'libx264':
                return 'mp4';
        }

        return null;
    }
}
