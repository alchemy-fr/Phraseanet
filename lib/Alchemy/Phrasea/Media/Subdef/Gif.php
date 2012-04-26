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

class Gif extends Image
{
    const OPTION_DELAY = 'delay';

    public function __construct()
    {
        parent::__construct();

        $this->registerOption(new OptionType\Range(self::OPTION_DELAY, 50, 500, 100));
    }

    public function getType()
    {
        return self::TYPE_ANIMATION;
    }

    public function getDescription()
    {
        return _('Generates an animated Gif file');
    }

    public function getMediaAlchemystSpec()
    {
        if ( ! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Animation();
        }

        $size = $this->getOption(self::OPTION_SIZE)->getValue();
        $resolution = $this->getOption(self::OPTION_RESOLUTION)->getValue();

        $this->spec->setDelay($this->getOption(self::OPTION_DELAY)->getValue());
        $this->spec->setDimensions($size, $size);
        $this->spec->setQuality($this->getOption(self::OPTION_QUALITY)->getValue());
        $this->spec->setStrip($this->getOption(self::OPTION_STRIP)->getValue());
        $this->spec->setResolution($resolution, $resolution);

        return $this->spec;
    }
}
