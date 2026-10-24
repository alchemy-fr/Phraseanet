<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaVorus\Media;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use MediaVorus\Utils\RawImageMimeTypeGuesser;

/**
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 *
 * @todo refactor Meta resolver to an independant object
 */
class Image extends DefaultMedia
{
    /**
     * Orientation constant Horizontal (normal)
     */
    const ORIENTATION_0 = 0;
    /**
     * Orientation constant Vertical (90 CW)
     */
    const ORIENTATION_90 = 90;
    /**
     * Orientation constant Vertical (270 CW)
     */
    const ORIENTATION_270 = 270;
    /**
     * Orientation constant Horizontal (reversed)
     */
    const ORIENTATION_180 = 180;
    /**
     * Colorspace constant CMYK
     */
    const COLORSPACE_CMYK = 'CMYK';
    /**
     * Colorspace constant RGB
     */
    const COLORSPACE_RGB = 'RGB';
    /**
     * Colorspace constant sRGB
     */
    const COLORSPACE_SRGB = 'sRGB';
    /**
     * Colorspace constant Grayscale
     */
    const COLORSPACE_GRAYSCALE = 'Grayscale';
    /**
     * Colorspace constant RGBA
     */
    const COLORSPACE_RGBA = 'RGBA';

    /**
     * @return string
     */
    #[VirtualProperty]
    public function getType()
    {
        return self::TYPE_IMAGE;
    }

    /**
     * Returns true if the document is a "Raw" image
     *
     * @return boolean
     */
    #[VirtualProperty]
    #[SerializedName('raw_image')]
    public function isRawImage()
    {
        return in_array($this->getFile()->getMimeType(), RawImageMimeTypeGuesser::$rawMimeTypes);
    }

    /**
     * Returns true if the document has multiple layers.
     * This method is supposed to be used to extract layer 0 with ImageMagick
     *
     * @return type
     */
    #[VirtualProperty]
    #[SerializedName('multiple_layers')]
    public function hasMultipleLayers()
    {
        return in_array($this->getFile()->getMimeType(), array(
                'image/tiff',
                'application/pdf',
                'image/psd',
                'image/vnd.adobe.photoshop',
                'image/photoshop',
                'image/ai',
                'image/illustrator',
                'image/vnd.adobe.illustrator'
            ));
    }

    /**
     * Return the width, null on error
     *
     * @return int
     */
    #[VirtualProperty]
    public function getWidth()
    {
        if ($this->getMetadatas()->containsKey('File:ImageWidth')) {
            return (int) $this->getMetadatas()->get('File:ImageWidth')->getValue()->asString();
        }

        if ($this->getMetadatas()->containsKey('Composite:ImageSize')) {
            $dimensions = $this->extractFromDimensions(
                $this->getMetadatas()->get('Composite:ImageSize')->getValue()->asString()
            );

            if ($dimensions) {
                return (int) $dimensions['width'];
            }
        }

        $sources = array('SubIFD:ImageWidth', 'IFD0:ImageWidth', 'ExifIFD:ExifImageWidth');

        return $this->castValue($this->findInSources($sources), 'int');
    }

    /**
     * Return the height, null on error
     *
     * @return int
     */
    #[VirtualProperty]
    public function getHeight()
    {
        if ($this->getMetadatas()->containsKey('File:ImageHeight')) {
            return (int) $this->getMetadatas()->get('File:ImageHeight')->getValue()->asString();
        }

        if ($this->getMetadatas()->containsKey('Composite:ImageSize')) {
            $dimensions = $this->extractFromDimensions(
                $this->getMetadatas()->get('Composite:ImageSize')->getValue()->asString()
            );

            if ($dimensions) {
                return (int) $dimensions['height'];
            }
        }

        $sources = array('SubIFD:ImageHeight', 'IFD0:ImageHeight', 'ExifIFD:ExifImageHeight');

        return $this->castValue($this->findInSources($sources), 'int');
    }

    /**
     * Return the number of channels (samples per pixel), null on error
     *
     * @return int
     */
    #[VirtualProperty]
    public function getChannels()
    {
        $sources = array('File:ColorComponents', 'IFD0:SamplesPerPixel');

        return $this->castValue($this->findInSources($sources), 'int');
    }

    /**
     * Return the focal length used by the camera in mm, null on error
     *
     * @return float
     */
    #[VirtualProperty]
    public function getFocalLength()
    {
        $sources = array('ExifIFD:FocalLength', 'XMP-exif:FocalLength');

        return $this->castValue($this->findInSources($sources), 'float');
    }

    /**
     * Return the color depth (bits per sample), null on error
     *
     * @return int
     */
    #[VirtualProperty]
    public function getColorDepth()
    {
        $sources = array('File:BitsPerSample', 'IFD0:BitsPerSample');

        return $this->castValue($this->findInSources($sources), 'int');
    }

    /**
     * Return the camera model, null on error
     *
     * @return string
     */
    #[VirtualProperty]
    public function getCameraModel()
    {
        $sources = array('IFD0:Model', 'IFD0:UniqueCameraModel');

        return $this->findInSources($sources);
    }

    /**
     * Return true if the Flash has been fired, false if it has not been
     * fired, null if does not know
     *
     * @return boolean
     */
    #[VirtualProperty]
    public function getFlashFired()
    {
        if (null !== $value = $this->findInSources(array('ExifIFD:Flash', 'Composite:Flash'))) {
            if (!is_numeric($value)) {
                return null;
            }

            switch ((int) $value % 2) {
                case 0: // not triggered
                    return false;
                    break;
                case 1: // triggered
                    return true;
                    break;
            }
        }

        return null;
    }

    /**
     * Get Aperture value
     *
     * @return float
     */
    #[VirtualProperty]
    public function getAperture()
    {
        return $this->castValue($this->findInSources(array('Composite:Aperture')), 'float');
    }

    /**
     * Get ShutterSpeed value in seconds
     *
     * @return float
     */
    #[VirtualProperty]
    public function getShutterSpeed()
    {
        return $this->castValue($this->findInSources(array('Composite:ShutterSpeed')), 'float');
    }

    /**
     * Returns one one the ORIENTATION_* constants, the degrees value of Orientation
     *
     * @return int
     */
    #[VirtualProperty]
    public function getOrientation()
    {
        switch ($this->findInSources(array('IFD0:Orientation'))) {
            case 6:
                return self::ORIENTATION_90;
                break;
            case 8:
                return self::ORIENTATION_270;
                break;
            case 1:
                return self::ORIENTATION_0;
                break;
            case 3:
                return self::ORIENTATION_180;
                break;
        }

        return null;
    }

    /**
     * Returns the Creation Date
     *
     * @todo rename in getDateTaken to avoid conflicts with the original file
     * properties, return a DateTime object
     *
     * @return string
     */
    #[VirtualProperty]
    public function getCreationDate()
    {
        $sources = array('IPTC:DateCreated', 'ExifIFD:DateTimeOriginal');

        return $this->findInSources($sources);
    }

    /**
     * Return the Hyperfocal Distance in meters
     *
     * @return float
     */
    #[VirtualProperty]
    public function getHyperfocalDistance()
    {

        return $this->castValue($this->findInSources(array('Composite:HyperfocalDistance')), 'float');
    }

    /**
     * Return the ISO value
     *
     * @return int
     */
    #[VirtualProperty]
    #[SerializedName('ISO')]
    public function getISO()
    {
        $sources = array('ExifIFD:ISO', 'IFD0:ISO');

        return $this->castValue($this->findInSources($sources), 'int');
    }

    /**
     * Return the Light Value
     *
     * @return float
     */
    #[VirtualProperty]
    public function getLightValue()
    {

        return $this->castValue($this->findInSources(array('Composite:LightValue')), 'float');
    }

    /**
     * Returns the colorspace as one of the COLORSPACE_* constants
     *
     * @return string
     */
    #[VirtualProperty]
    public function getColorSpace()
    {
        $regexp = '/.*:(colorspace|colormode|colorspacedata|colortype)/i';

        foreach ($this->getMetadatas()->filterKeysByRegExp($regexp) as $meta) {
            switch (strtolower(trim($meta->getValue()->asString()))) {
                case 'cmyk':
                    return self::COLORSPACE_CMYK;
                    break;
                case 'srgb':
                    return self::COLORSPACE_SRGB;
                    break;
                case 'rgb':
                case '2':
                    return self::COLORSPACE_RGB;
                    break;
                case 'grayscale':
                case '0':
                    return self::COLORSPACE_GRAYSCALE;
                    break;
                case 'rgba':    
                case '6':
                    return self::COLORSPACE_RGBA;
                    break;
            }
        }

        switch ($this->findInSources(array('File:ColorComponents'))) {
            case 1:
                return self::COLORSPACE_GRAYSCALE;
                break;
            case 3:
                return self::COLORSPACE_RGB;
                break;
            case 4:
                return self::COLORSPACE_CMYK;
                break;
        }

        return null;
    }

    /**
     * Extract the width and height from a widthXheight serialized value
     * Returns an array with width and height keys, null on error
     *
     * @param type $WidthXHeight
     * @return array
     */
    protected function extractFromDimensions($WidthXHeight)
    {
        preg_match("/(\d+)\D+(\d+)/", $WidthXHeight, $values);

        if (count($values) === 3 && ctype_digit($values[1]) && ctype_digit($values[2])) {
            return array('width'  => $values[1], 'height' => $values[2]);
        }

        return null;
    }
}
