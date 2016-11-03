<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Phrasea\Media\Subdef\Audio;
use Alchemy\Phrasea\Media\Subdef\Video;
use Alchemy\Phrasea\Media\Subdef\FlexPaper;
use Alchemy\Phrasea\Media\Subdef\Gif;
use Alchemy\Phrasea\Media\Subdef\Unknown;
use Alchemy\Phrasea\Media\Subdef\Subdef as SubdefSpecs;
use Alchemy\Phrasea\Media\Type\Type as SubdefType;
use MediaAlchemyst\Specification\SpecificationInterface;
use Symfony\Component\Translation\TranslatorInterface;

class databox_subdef
{
    /**
     * The class type of the subdef
     * Is null or one of the CLASS_* constants
     *
     * @var string
     */
    protected $class;
    protected $devices = [];
    protected $name;
    protected $path;
    protected $subdef_group;
    protected $labels = [];

    /**
     * @var bool
     */
    private $requiresMetadataUpdate;
    protected $downloadable;
    protected $translator;
    protected static $mediaTypeToSubdefTypes = [
        SubdefType::TYPE_AUDIO => [SubdefSpecs::TYPE_IMAGE, SubdefSpecs::TYPE_AUDIO],
        SubdefType::TYPE_DOCUMENT => [SubdefSpecs::TYPE_IMAGE, SubdefSpecs::TYPE_FLEXPAPER],
        SubdefType::TYPE_FLASH => [SubdefSpecs::TYPE_IMAGE],
        SubdefType::TYPE_IMAGE => [SubdefSpecs::TYPE_IMAGE],
        SubdefType::TYPE_VIDEO => [SubdefSpecs::TYPE_IMAGE, SubdefSpecs::TYPE_VIDEO, SubdefSpecs::TYPE_ANIMATION],
        SubdefType::TYPE_UNKNOWN => [SubdefSpecs::TYPE_IMAGE],
    ];

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
     * @param SubdefType       $type
     * @param SimpleXMLElement $sd
     *
     * @return databox_subdef
     */
    public function __construct(SubdefType $type, SimpleXMLElement $sd, TranslatorInterface $translator)
    {
        $this->subdef_group = $type;
        $this->class = (string) $sd->attributes()->class;
        $this->translator = $translator;

        foreach ($sd->devices as $device) {
            $this->devices[] = (string) $device;
        }

        $this->name = strtolower($sd->attributes()->name);
        $this->downloadable = p4field::isyes($sd->attributes()->downloadable);
        $this->path = trim($sd->path) !== '' ? p4string::addEndSlash(trim($sd->path)) : '';

        $this->requiresMetadataUpdate = p4field::isyes((string) $sd->meta);

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
            case SubdefSpecs::TYPE_UNKNOWN:
                $this->subdef_type = $this->buildImageSubdef($sd);
                break;
        }
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

    public function get_label($code, $substitute = true)
    {
        if (empty($this->labels[$code]) && $substitute) {
            return $this->get_name();
        } elseif (isset($this->labels[$code])) {
            return $this->labels[$code];
        }

        return null;
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
        $subdefTypes = [];

        $availableDevices = [
            self::DEVICE_ALL,
            self::DEVICE_HANDHELD,
            self::DEVICE_PRINT,
            self::DEVICE_PROJECTION,
            self::DEVICE_SCREEN,
            self::DEVICE_TV,
        ];

        if (isset(self::$mediaTypeToSubdefTypes[$this->subdef_group->getType()])) {

            foreach (self::$mediaTypeToSubdefTypes[$this->subdef_group->getType()] as $subdefType) {

                if ($subdefType == $this->subdef_type->getType()) {
                    $mediatype_obj = $this->subdef_type;
                } else {
                    switch ($subdefType) {
                        case SubdefSpecs::TYPE_ANIMATION:
                            $mediatype_obj = new Gif($this->translator);
                            break;
                        case SubdefSpecs::TYPE_AUDIO:
                            $mediatype_obj = new Audio($this->translator);
                            break;
                        case SubdefSpecs::TYPE_FLEXPAPER:
                            $mediatype_obj = new FlexPaper($this->translator);
                            break;
                        case SubdefSpecs::TYPE_IMAGE:
                            $mediatype_obj = new Image($this->translator);
                            break;
                        case SubdefSpecs::TYPE_VIDEO:
                            $mediatype_obj = new Video($this->translator);
                            break;
                        case SubdefSpecs::TYPE_UNKNOWN:
                            $mediatype_obj = new Unknown($this->translator);
                            break;
                        default:
                            continue;
                            break;
                    }
                }

                $mediatype_obj->registerOption(new \Alchemy\Phrasea\Media\Subdef\OptionType\Multi($this->translator->trans('Target Device'), 'devices', $availableDevices, $this->devices));

                $subdefTypes[] = $mediatype_obj;
            }
        }

        return $subdefTypes;
    }

    /**
     * Tells us if we have to write meta data in the subdef
     *
     * @return bool
     */
    public function isMetadataUpdateRequired()
    {
        return $this->requiresMetadataUpdate;
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
     * @return SpecificationInterface
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
     * @param  SimpleXMLElement $sd
     * @return Image
     */
    protected function buildImageSubdef(SimpleXMLElement $sd)
    {
        $image = new Image($this->translator);

        if ($sd->icodec) {
            $image->setOptionValue(Image::OPTION_ICODEC, (string) $sd->icodec);
        }
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
        if ($sd->flatten) {
            $image->setOptionValue(Image::OPTION_FLATTEN, p4field::isyes($sd->flatten));
        }

        return $image;
    }

    /**
     * Build Audio Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement $sd
     * @return Audio
     */
    protected function buildAudioSubdef(SimpleXMLElement $sd)
    {
        $audio = new Audio($this->translator);

        if ($sd->acodec) {
            $audio->setOptionValue(Audio::OPTION_ACODEC, (string) $sd->acodec);
        }
        if ($sd->audiobitrate) {
            $audio->setOptionValue(Audio::OPTION_AUDIOBITRATE, (int) $sd->audiobitrate);
        }
        if ($sd->audiosamplerate) {
            $audio->setOptionValue(Audio::OPTION_AUDIOSAMPLERATE, (int) $sd->audiosamplerate);
        }

        return $audio;
    }

    /**
     * Build Flexpaper Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement                    $sd
     * @return \Alchemy\Phrasea\Media\Subdef\Video
     */
    protected function buildFlexPaperSubdef(SimpleXMLElement $sd)
    {
        return new FlexPaper($this->translator);
    }

    /**
     * Build GIF Subdef object depending the SimpleXMLElement
     *
     * @param  SimpleXMLElement $sd
     * @return Gif
     */
    protected function buildGifSubdef(SimpleXMLElement $sd)
    {
        $gif = new Gif($this->translator);

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
     * @param  SimpleXMLElement $sd
     * @return Video
     */
    protected function buildVideoSubdef(SimpleXMLElement $sd)
    {
        $video = new Video($this->translator);

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
        if ($sd->audiobitrate) {
            $video->setOptionValue(Video::OPTION_AUDIOBITRATE, (int) $sd->audiobitrate);
        }
        if ($sd->audiosamplerate) {
            $video->setOptionValue(Video::OPTION_AUDIOSAMPLERATE, (int) $sd->audiosamplerate);
        }
        if ($sd->GOPsize) {
            $video->setOptionValue(Video::OPTION_GOPSIZE, (int) $sd->GOPsize);
        }

        return $video;
    }
}
