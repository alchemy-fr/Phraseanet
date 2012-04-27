<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef;

/**
 * Video Subdef
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Video extends Audio
{
    const OPTION_SIZE = 'size';
    const OPTION_FRAMERATE = 'fps';
    const OPTION_VCODEC = 'vcodec';
    const OPTION_GOPSIZE = 'GOPsize';

    protected $options = array();

    public function __construct()
    {
        parent::__construct();

        $this->registerOption(new OptionType\Range(_('GOP size'), self::OPTION_GOPSIZE, 1, 200, 25));
        $this->registerOption(new OptionType\Range(_('Dimension'), self::OPTION_SIZE, 100, 2000, 600, 16));
        $this->registerOption(new OptionType\Range(_('Frame Rate'), self::OPTION_FRAMERATE, 1, 200, 20));
        $this->registerOption(new OptionType\Enum(_('Video Codec'), self::OPTION_VCODEC, array('libx264', 'libvpx', 'libtheora'), 'libx264'));
    }

    public function getType()
    {
        return self::TYPE_VIDEO;
    }

    public function getDescription()
    {
        return _('Generates a video file');
    }

    public function getMediaAlchemystSpec()
    {
        if ( ! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Video();
        }

        $size = $this->getOption(self::OPTION_SIZE)->getValue();

        $this->spec->setAudioCodec($this->getOption(self::OPTION_ACODEC)->getValue());
        $this->spec->setAudioSampleRate($this->getOption(self::OPTION_AUDIOSAMPLERATE)->getValue());
        $this->spec->setKiloBitrate($this->getOption(self::OPTION_BITRATE)->getValue());
        $this->spec->setVideoCodec($this->getOption(self::OPTION_VCODEC)->getValue());
        $this->spec->setDimensions($size, $size);
        $this->spec->setFramerate($this->getOption(self::OPTION_FRAMERATE)->getValue());
        $this->spec->setGOPSize($this->getOption(self::OPTION_GOPSIZE)->getValue());

        return $this->spec;
    }
}
