<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef;

/**
 * Audio Subdef
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Audio extends Provider
{
    const OPTION_BITRATE = 'bitrate';
    const OPTION_THREADS = 'threads';
    const OPTION_ACODEC = 'acodec';
    const OPTION_AUDIOSAMPLERATE = 'audiosamplerate';

    public function __construct()
    {
        $AVaudiosamplerate = array(
            8000, 11025, 16000, 22050, 32000, 44056, 44100,
            47250, 48000, 50000, 50400, 88200, 96000
        );

        $this->registerOption(new OptionType\Range(_('Birate'), self::OPTION_BITRATE, 100, 4000, 800));
        $this->registerOption(new OptionType\Range(_('Threads'), self::OPTION_THREADS, 1, 16, 1));
        $this->registerOption(new OptionType\Enum(_('AudioSamplerate'), self::OPTION_AUDIOSAMPLERATE, $AVaudiosamplerate));
        $this->registerOption(new OptionType\Enum(_('Audio Codec'), self::OPTION_ACODEC, array('libmp3lame', 'flac'), 'libmp3lame'));
    }

    public function getType()
    {
        return self::TYPE_AUDIO;
    }

    public function getDescription()
    {
        return _('Generates an audio file');
    }

    public function getMediaAlchemystSpec()
    {
        if ( ! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Audio();
        }

        $this->spec->setAudioCodec($this->getOption(self::OPTION_ACODEC)->getValue());
        $this->spec->setAudioSampleRate($this->getOption(self::OPTION_AUDIOSAMPLERATE)->getValue());
        $this->spec->setKiloBitrate($this->getOption(self::OPTION_BITRATE)->getValue());

        return $this->spec;
    }
}
