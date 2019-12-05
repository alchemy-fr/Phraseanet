<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef;

use Symfony\Component\Translation\TranslatorInterface;

class Video extends Audio
{
    const OPTION_SIZE = 'size';
    const OPTION_BITRATE = 'bitrate';
    const OPTION_FRAMERATE = 'fps';
    const OPTION_VCODEC = 'vcodec';
    const OPTION_GOPSIZE = 'GOPsize';

    protected $options = [];

    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);

        $this->registerOption(new OptionType\Range($this->translator->trans('Bitrate'), self::OPTION_BITRATE, 100, 12000, 800));
        $this->registerOption(new OptionType\Range($this->translator->trans('GOP size'), self::OPTION_GOPSIZE, 1, 300, 10));
        $this->registerOption(new OptionType\Range($this->translator->trans('Dimension'), self::OPTION_SIZE, 64, 2000, 600, 16));
        $this->registerOption(new OptionType\Range($this->translator->trans('Frame Rate'), self::OPTION_FRAMERATE, 1, 200, 20));
        $this->registerOption(new OptionType\Enum($this->translator->trans('Video Codec'), self::OPTION_VCODEC, ['libx264', 'libvpx', 'libtheora'], 'libx264'));
        $this->unregisterOption(self::OPTION_ACODEC);
        $this->registerOption(new OptionType\Enum($this->translator->trans('Audio Codec'), self::OPTION_ACODEC, ['libfaac', 'libvo_aacenc', 'libmp3lame', 'libvorbis', 'libfdk_aac'], 'libmp3lame'));
    }

    public function getType()
    {
        return self::TYPE_VIDEO;
    }

    public function getDescription()
    {
        return $this->translator->trans('Generates a video file');
    }

    public function getMediaAlchemystSpec()
    {
        if (! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Video();
        }

        $size = $this->getOption(self::OPTION_SIZE)->getValue();

        $this->spec->setAudioCodec($this->getOption(self::OPTION_ACODEC)->getValue());
        $this->spec->setAudioSampleRate($this->getOption(self::OPTION_AUDIOSAMPLERATE)->getValue());
        $this->spec->setAudioKiloBitrate($this->getOption(self::OPTION_AUDIOBITRATE)->getValue());
        $this->spec->setKiloBitrate($this->getOption(self::OPTION_BITRATE)->getValue());
        $this->spec->setVideoCodec($this->getOption(self::OPTION_VCODEC)->getValue());
        $this->spec->setDimensions($size, $size);
        $this->spec->setFramerate($this->getOption(self::OPTION_FRAMERATE)->getValue());
        $this->spec->setGOPSize($this->getOption(self::OPTION_GOPSIZE)->getValue());

        return $this->spec;
    }
}
