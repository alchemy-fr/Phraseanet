<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst\Transmuter;

use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Image as ImagickImage;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaAlchemyst\Exception\InvalidArgumentException;
use MediaVorus\Media\MediaInterface;
use Neutron\TemporaryFilesystem\Manager;
use Pimple\Container;

abstract class AbstractTransmuter
{
    /**
     *
     * @var Container
     */
    protected $container;
    /** @var Manager */
    protected $tmpFileManager;

    const TMP_FILE_SCOPE = '_media_alchemyst_';

    public function __construct(Container $container, Manager $manager)
    {
        $this->container = $container;
        $this->tmpFileManager = $manager;
    }

    public function __destruct()
    {
        $this->container = null;
    }

    /**
     * Return the box for a spec
     *
     * @param Image   $spec
     * @param integer $width
     * @param integer $height
     *
     * @return Box|null
     */
    protected function boxFromSize(Image $spec, $width, $height)
    {
        if ( ! $spec->getWidth() && ! $spec->getHeight()) {
            throw new InvalidArgumentException('The specification you provide must have width and height');
        }

        if ($spec->getWidth() >= $width && $spec->getHeight() >= $height) {
            return null;
        }

        if ($spec->getResizeMode() == Image::RESIZE_MODE_INBOUND_FIXEDRATIO) {

            $ratioOut = $spec->getWidth() / $spec->getHeight();
            $ratioIn = $width / $height;

            if ($ratioOut > $ratioIn) {

                $outHeight = round($spec->getHeight());
                $outWidth = round($ratioIn * $outHeight);
            } else {

                $outWidth = round($spec->getWidth());
                $outHeight = round($outWidth / $ratioIn);
            }

            return new Box($outWidth, $outHeight);
        }

        return new Box($spec->getWidth(), $spec->getHeight());
    }

    /**
     * Composites the image over an opaque background color.
     *
     * If no background color is provided, the image is composited over a white
     * background, only if it has an alpha channel (or if it can not be detected).
     *
     * @param ImageInterface    $image
     * @param ImagineInterface  $imagine
     * @param string|null       $backgroundColor A color understood by Imagine RGB palette (e.g. "#FFFFFF")
     *
     * @return ImageInterface
     */
    protected function removeAlpha(ImageInterface $image, ImagineInterface $imagine, $backgroundColor = null)
    {
        $backgroundColor = null === $backgroundColor ? '' : trim($backgroundColor);

        if ('' === $backgroundColor) {
            if ($image instanceof ImagickImage && !$image->getImagick()->getImageAlphaChannel()) {
                return $image;
            }

            $backgroundColor = '#FFFFFF';
        }

        $palette = new RGB();
        $background = $imagine->create($image->getSize(), $palette->color($backgroundColor, 100));
        $background->paste($image, new Point(0, 0));

        return $background;
    }

    abstract public function execute(SpecificationInterface $spec, MediaInterface $source, $dest);
}
