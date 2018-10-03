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

use MediaAlchemyst\Specification\Image as ImageSpecification;
use Symfony\Component\Translation\TranslatorInterface;

class Unknown extends Provider
{
    const OPTION_SIZE = 'size';
    const OPTION_RESOLUTION = 'resolution';
    const OPTION_STRIP = 'strip';
    const OPTION_QUALITY = 'quality';
    const OPTION_FLATTEN = 'flatten';
    const OPTION_ICODEC = 'icodec';

    protected $options = [];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->registerOption(new OptionType\Range($this->translator->trans('Dimension'), self::OPTION_SIZE, 20, 3000, 800));
        $this->registerOption(new OptionType\Range($this->translator->trans('Resolution'), self::OPTION_RESOLUTION, 50, 300, 72));
        $this->registerOption(new OptionType\Boolean($this->translator->trans('Remove ICC Profile'), self::OPTION_STRIP, false));
        $this->registerOption(new OptionType\Boolean($this->translator->trans('Flatten layers'), self::OPTION_FLATTEN, false));
        $this->registerOption(new OptionType\Range($this->translator->trans('Quality'), self::OPTION_QUALITY, 0, 100, 75));
        $this->registerOption(new OptionType\Enum('Image Codec', self::OPTION_ICODEC, array('jpeg', 'png', 'tiff'), 'jpeg'));
    }

    public function getType()
    {
        return self::TYPE_IMAGE;
    }

    public function getDescription()
    {
        return $this->translator->trans('Generates an image');
    }

    public function getMediaAlchemystSpec()
    {
        if (! $this->spec) {
            $this->spec = new ImageSpecification();
        }

        $size = $this->getOption(self::OPTION_SIZE)->getValue();
        $resolution = $this->getOption(self::OPTION_RESOLUTION)->getValue();

        $this->spec->setImageCodec($this->getOption(self::OPTION_ICODEC)->getValue());
        $this->spec->setResizeMode(ImageSpecification::RESIZE_MODE_INBOUND_FIXEDRATIO);
        $this->spec->setDimensions($size, $size);
        $this->spec->setQuality($this->getOption(self::OPTION_QUALITY)->getValue());
        $this->spec->setStrip($this->getOption(self::OPTION_STRIP)->getValue());
        $this->spec->setFlatten($this->getOption(self::OPTION_FLATTEN)->getValue());
        $this->spec->setResolution($resolution, $resolution);

        return $this->spec;
    }
}
