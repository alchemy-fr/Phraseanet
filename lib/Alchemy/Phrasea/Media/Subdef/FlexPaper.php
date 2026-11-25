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

use Alchemy\Phrasea\Media\Subdef\Specification\FlexPaperSpecification;

/**
 * Legacy document to SWF subdefinition: kept so that existing databox structures still load,
 * but these subdefinitions cannot be generated anymore.
 */
class FlexPaper extends Provider
{
    protected $options = [];

    public function __construct(\Symfony\Contracts\Translation\TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getType()
    {
        return self::TYPE_FLEXPAPER;
    }

    public function getDescription()
    {
        return $this->translator->trans('Generates a flexpaper flash file');
    }

    public function getMediaAlchemystSpec()
    {
        if (! $this->spec) {
            $this->spec = new FlexPaperSpecification();
        }

        return $this->spec;
    }
}
