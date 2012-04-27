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
 * Image Subdef
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Image extends Provider
{
    const OPTION_SIZE = 'size';
    const OPTION_RESOLUTION = 'resolution';
    const OPTION_STRIP = 'strip';
    const OPTION_QUALITY = 'quality';

    protected $options = array();

    public function __construct()
    {
        $this->registerOption(new OptionType\Range(_('Dimension'), self::OPTION_SIZE, 20, 3000, 800));
        $this->registerOption(new OptionType\Range(_('Resolution'), self::OPTION_RESOLUTION, 50, 300, 72));
        $this->registerOption(new OptionType\Boolean(_('Remove ICC Profile'), self::OPTION_STRIP, false));
        $this->registerOption(new OptionType\Range(_('Quality'), self::OPTION_QUALITY, 0, 100, 75));
    }

    public function getType()
    {
        return self::TYPE_IMAGE;
    }

    public function getDescription()
    {
        return _('Generates a Jpeg image');
    }

    public function getMediaAlchemystSpec()
    {
        if ( ! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Image();
        }

        $size = $this->getOption(self::OPTION_SIZE)->getValue();
        $resolution = $this->getOption(self::OPTION_RESOLUTION)->getValue();

        $this->spec->setDimensions($size, $size);
        $this->spec->setQuality($this->getOption(self::OPTION_QUALITY)->getValue());
        $this->spec->setStrip($this->getOption(self::OPTION_STRIP)->getValue());
        $this->spec->setResolution($resolution, $resolution);

        return $this->spec;
    }
}
