<?php

namespace Alchemy\Phrasea\Core\Thumbnail;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use MediaVorus\Media\Image;
use MediaVorus\Media\MediaInterface;
use MediaVorus\Media\Video;
use Symfony\Component\HttpFoundation\File\File;

//use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractThumbnailManager
{
    /**
     * @var string[]
     */
    protected static $allowedMimeTypes = [
        'image/gif',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/pjpeg'
    ];

    /**
     * @var Alchemyst
     */
    protected $alchemyst;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @param Application $application
     * @param Alchemyst $alchemyst
     * @param Filesystem $filesystem
     * @param $rootPath
     */
    public function __construct(Application $application, Alchemyst $alchemyst, Filesystem $filesystem, $rootPath)
    {
        $this->alchemyst = $alchemyst;
        $this->application = $application;
        $this->filesystem = $filesystem;
        $this->rootPath = $rootPath;
    }

    protected function validateFileMimeType(File $file)
    {
        if (!in_array(mb_strtolower($file->getMimeType()), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file format');
        }
    }

    /**
     * @param MediaInterface $media
     * @param int $maxWidth
     * @param int $maxHeight
     * @return bool
     */
    protected function shouldResize($media, $maxWidth, $maxHeight)
    {
        if (! $media instanceof Image && ! $media instanceof Video) {
            return false;
        }

        return $media->getWidth() > $maxWidth || $media->getHeight() > $maxHeight;
    }

    /**
     * @param ImageSpecification $imageSpec
     * @param int $width
     * @param int $height
     */
    protected function setSpecificationSize(ImageSpecification $imageSpec, $width, $height)
    {
        $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
        $imageSpec->setDimensions($width, $height);
    }

    /**
     * @param File $file
     * @param $imageSpec
     * @return string
     * @throws \MediaAlchemyst\Exception\FileNotFoundException
     */
    protected function resizeMediaFile(File $file, $imageSpec)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tmpdatabox') . '.jpg';
        $this->alchemyst->turninto($file->getPathname(), $tmp, $imageSpec);

        return $tmp;
    }

    /**
     * @param string $target
     * @param string $filename
     */
    protected function copyFile($target, $filename)
    {
        if (is_file($target)) {
            $this->filesystem->remove($target);
        }

        if (null === $target || null === $filename) {
            return;
        }

        $this->filesystem->mkdir(dirname($target), 0750);
        $this->filesystem->copy($filename, $target, true);
        $this->filesystem->chmod($target, 0760);
    }
}
