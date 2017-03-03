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

class Gif extends Provider
{
    const OPTION_DELAY = 'delay';
    const OPTION_SIZE = 'size';

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->registerOption(new OptionType\Range($this->translator->trans('Dimension'), self::OPTION_SIZE, 20, 3000, 800));
        $this->registerOption(new OptionType\Range($this->translator->trans('Delay'), self::OPTION_DELAY, 50, 500, 100));
    }

    public function getType()
    {
        return self::TYPE_ANIMATION;
    }

    public function getDescription()
    {
        return $this->translator->trans('Generates an animated Gif file');
    }

    public function getMediaAlchemystSpec()
    {
        if (! $this->spec) {
            $this->spec = new \MediaAlchemyst\Specification\Animation();
        }

        $size = $this->getOption(self::OPTION_SIZE)->getValue();

        $this->spec->setDelay($this->getOption(self::OPTION_DELAY)->getValue());
        $this->spec->setDimensions($size, $size);

        return $this->spec;
    }
}
