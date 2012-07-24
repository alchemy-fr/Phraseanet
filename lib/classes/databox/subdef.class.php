<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Phrasea\Media\Subdef\Audio;
use Alchemy\Phrasea\Media\Subdef\Video;
use Alchemy\Phrasea\Media\Subdef\FlexPaper;
use Alchemy\Phrasea\Media\Subdef\Gif;
use Alchemy\Phrasea\Media\Subdef\Subdef as SubdefSpecs;
use Alchemy\Phrasea\Media\Type\Type as SubdefType;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_subdef
{
    /**
     * The class type of the subdef
     * Is null or one of the CLASS_* constants
     *
     * @var string
     */
    protected $class;
    protected $devices = array();
    protected $name;
    protected $path;
    protected $subdef_group;
    protected $labels = array();
    protected $write_meta;
    protected $downloadable;
    protected static $mediaTypeToSubdefTypes = array(
        SubdefType::TYPE_AUDIO => array(SubdefSpecs::TYPE_IMAGE, SubdefSpecs::TYPE_AUDIO),
        SubdefType::TYPE_DOCUMENT => array(SubdefSpecs::TYPE_IMAGE, SubdefSpecs::TYPE_FLEXPAPER),
        SubdefType::TYPE_FLASH => array(SubdefSpecs::TYPE_IMAGE),
        SubdefType::TYPE_IMAGE => array(SubdefSpecs::TYPE_IMAGE),
        SubdefType::TYPE_VIDEO => array(SubdefSpecs::TYPE_IMAGE, SubdefSpecs::TYPE_VIDEO, SubdefSpecs::TYPE_ANIMATION),
    );

    const CLASS_THUMBNAIL = 'thumbnail';
    const CLASS_PREVIEW = 'preview';
    const CLASS_DOCUMENT = 'document';
    const DEVICE_ALL = 'all';
    const DEVICE_HANDHELD = 'handheld';
    const DEVICE_PRINT = 'print';
    const DEVICE_PROJECTION = 'projection';
    const DEVICE_SCREEN = 'screen';
    const DEVICE_TV = 'tv';

    /**
     *
     * @param  SimpleXMLElement $sd
     * @return databox_subdef
     */
    public function __construct(SubdefType $type, SimpleXMLElement $sd)
    {
        $this->subdef_group = $type;
        $this->class = (string) $sd->attributes()->class;

        foreach ($sd->devices as $device) {
            $this->devices[] = (string) $device;
        }

        $this->name = strtolower($sd->attributes()->name);
        $this->downloadable = p4field::isyes($sd->attributes()->downloadable);
        $this->path = trim($sd->path) !== '' ? p4string::addEndSlash(trim($sd->path)) : '';

        $this->write_meta = p4field::isyes((string) $sd->meta);

        foreach ($sd->label as $label) {
            $lang = trim((string) $label->attributes()->lang);

            if ($lang) {
                $this->labels[$lang] = (string) $label;
            }
        }

        switch ((string) $sd->mediatype) {
            default:
            case SubdefSpecs::TYPE_IMAGE:
                $this->subdef_type = $this->buildImageSubdef($sd);
                break;
            case SubdefSpecs::TYPE_AUDIO:
                $this->subdef_type = $this->buildAudioSubdef($sd);
                break;
            case SubdefSpecs::TYPE_VIDEO:
                $this->subdef_type = $this->buildVideoSubdef($sd);
                break;
            case SubdefSpecs::TYPE_ANIMATION:
                $this->subdef_type = $this->buildGifSubdef($sd);
                break;
            case SubdefSpecs::TYPE_FLEXPAPER:
                $this->subdef_type = $this->buildFlexPaperSubdef($sd);
                break;
        }

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_class()
    {
        return $this->class;
    }

    /**
     *
     * @return string
     */
    public function get_path()
    {
        return $this->path;
    }

    /**
     * The devices matching this subdefinition
     *
     * @return Array
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * The current SubdefType the subdef converts documents
     *
     * @return Alchemy\Phrasea\Media\Subdef\Subdef
     */
    public function getSubdefType()
    {
        return $this->subdef_type;
    }

    /**
     * The current Group which the subdef is in (Audio, Video ...)
     *
     * @return Alchemy\Phrasea\Media\Type\Type
     */
    public function getSubdefGroup()
    {
        return $this->subdef_group;
    }

    /**
     * An associative label ; keys are i18n languages
     *
     * @return Array
     */
    public function get_labels()
    {
        return $this->labels;
    }

    /**
     * boolean
     *
     * @return type
     */
    public function is_downloadable()
    {
        return $this->downloadable;
    }

    /**
     * Get an array of Alchemy\Phrasea\Media\Subdef\Subdef available for the current Media Type
     *
     * @return array
     */
    public function getAvailableSubdefTypes()
    {
        $subdefTypes = array();

        $availableDevices = array(
            self::DEVICE_ALL,
            self::DEVICE_HANDHELD,
            self::DEVICE_PRINT,
            self::DEVICE_PROJECTION,
            self::DEVICE_SCREEN,
            self::DEVICE_TV,
        );

        if (isset(self::$mediaTypeToSubdefTypes[$this->subdef_group->getType()])) {

            foreach (self::$mediaTypeToSubdefTypes[$this->subdef_group->getType()] as $subdefType) {

                if ($subdefType == $this->subdef_type->getType()) {
                    $mediatype_obj = $this->subdef_type;
                } else {
                    switch ($subdefType) {
                        case SubdefSpecs::TYPE_ANIMATION:
                            $mediatype_obj = new Gif();
                            break;
                        case SubdefSpecs::TYPE_AUDIO:
                            $mediatype_obj = new Audio();
                            break;
                        case SubdefSpecs::TYPE_FLEXPAPER:
                            $mediatype_obj = new FlexPaper();
                            break;
                        case SubdefSpecs::TYPE_IMAGE:
                            $mediatype_obj = new Image();
                            break;
                        case SubdefSpecs::TYPE_VIDEO:
                            $mediatype_obj = new Video();
                            break;
                        default:
                            continue;
                            break;
                    }
                }

                $mediatype_obj->registerOption(new \Alchemy\Phrasea\Media\Subdef\OptionType\Multi(_('Target Device'), 'devices', $availableDevices, $this->devices));

                $subdefTypes[] = $mediatype_obj;
            }
        }

        return $subdefTypes;
    }

    /**
     * Tells us if we have to write meta datas in the subdef
     *
     * @return boolean
     */
    public function meta_writeable()
    {
        return $this->write_meta;
    }

    /**
     * The name of the subdef
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * Get the MediaAlchemyst specs for the current subdef
     *
     * @return type
     */
    public function getSpecs()
    {
        return $this->subdef_type->getMediaAlchemystSpec();
    }

    /**
     * An array of Alchemy\Phrasea\Media\Subdef\OptionType\OptionType for the current subdef
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->subdef_type->getOptions();
    }

    /**
     * Build Image Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement                    $sd
     * @return \Alchemy\Phrasea\Media\Subdef\Video
     */
    protected function buildImageSubdef(SimpleXMLElement $sd)
    {
        $image = new Image();

        if ($sd->size) {
            $image->setOptionValue(Image::OPTION_SIZE, (int) $sd->size);
        }
        if ($sd->quality) {
            $image->setOptionValue(Image::OPTION_QUALITY, (int) $sd->quality);
        }
        if ($sd->strip) {
            $image->setOptionValue(Image::OPTION_STRIP, p4field::isyes($sd->strip));
        }
        if ($sd->dpi) {
            $image->setOptionValue(Image::OPTION_RESOLUTION, (int) $sd->dpi);
        }

        return $image;
    }

    /**
     * Build Audio Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement                    $sd
     * @return \Alchemy\Phrasea\Media\Subdef\Video
     */
    protected function buildAudioSubdef(SimpleXMLElement $sd)
    {
        return new Audio();
    }

    /**
     * Build Flexpaper Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement                    $sd
     * @return \Alchemy\Phrasea\Media\Subdef\Video
     */
    protected function buildFlexPaperSubdef(SimpleXMLElement $sd)
    {
        return new FlexPaper();
    }

    /**
     * Build GIF Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement                    $sd
     * @return \Alchemy\Phrasea\Media\Subdef\Video
     */
    protected function buildGifSubdef(SimpleXMLElement $sd)
    {
        $gif = new Gif();

        if ($sd->size) {
            $gif->setOptionValue(Gif::OPTION_SIZE, (int) $sd->size);
        }
        if ($sd->delay) {
            $gif->setOptionValue(Gif::OPTION_DELAY, (int) $sd->delay);
        }

        return $gif;
    }

    /**
     * Build Video Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement                    $sd
     * @return \Alchemy\Phrasea\Media\Subdef\Video
     */
    protected function buildVideoSubdef(SimpleXMLElement $sd)
    {
        $video = new Video();

        if ($sd->size) {
            $video->setOptionValue(Video::OPTION_SIZE, (int) $sd->size);
        }
        if ($sd->acodec) {
            $video->setOptionValue(Video::OPTION_ACODEC, (string) $sd->acodec);
        }
        if ($sd->vcodec) {
            $video->setOptionValue(Video::OPTION_VCODEC, (string) $sd->vcodec);
        }
        if ($sd->fps) {
            $video->setOptionValue(Video::OPTION_FRAMERATE, (int) $sd->fps);
        }
        if ($sd->bitrate) {
            $video->setOptionValue(Video::OPTION_BITRATE, (int) $sd->bitrate);
        }

        return $video;
    }
}
