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

class Audio extends Provider
{
    const OPTION_AUDIOBITRATE = 'audiobitrate';
    const OPTION_THREADS = 'threads';
    const OPTION_ACODEC = 'acodec';
    const OPTION_AUDIOSAMPLERATE = 'audiosamplerate';
    const OPTION_AUDIOCHANNEL = 'audiochannel';

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $AVaudiosamplerate = [
            8000, 11025, 16000, 22050, 32000, 44056, 44100,
            47250, 48000, 50000, 50400, 88200, 96000
        ];

        $audioChannel = ['mono', 'stereo'];

        $this->registerOption(new OptionType\Range($this->translator->trans('Audio Birate'), self::OPTION_AUDIOBITRATE, 32, 320, 128, 32));
        $this->registerOption(new OptionType\Enum($this->translator->trans('AudioSamplerate'), self::OPTION_AUDIOSAMPLERATE, $AVaudiosamplerate));
        $this->registerOption(new OptionType\Enum($this->translator->trans('Audio Codec'), self::OPTION_ACODEC, ['libmp3lame', 'flac', 'pcm_s16le'], 'libmp3lame'));
        $this->registerOption(new OptionType\Enum($this->translator->trans('Audio channel'), self::OPTION_AUDIOCHANNEL, $audioChannel));
    }

    public function getType()
    {
        return self::TYPE_AUDIO;
    }

    public function getDescription()
    {
        return $this->translator->trans('Generates an audio file');
    }

    public function getMediaAlchemystSpec()
    {
        if (! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Audio();
        }

        $this->spec->setAudioCodec($this->getOption(self::OPTION_ACODEC)->getValue());
        $this->spec->setAudioSampleRate($this->getOption(self::OPTION_AUDIOSAMPLERATE)->getValue());
        $this->spec->setAudioKiloBitrate($this->getOption(self::OPTION_AUDIOBITRATE)->getValue());
        $this->spec->setAudioChannels($this->getChannelNumber($this->getOption(self::OPTION_AUDIOCHANNEL)->getValue()));

        return $this->spec;
    }

    private function getChannelNumber($audioChannel)
    {
        switch($audioChannel)
        {
            case 'mono':
                return 1;
            case 'stereo':
                return 2;
            default:
                return null;
        }
    }
}
