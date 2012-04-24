<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Subdef;

class FlexPaper extends Provider
{

    protected $options = array();

    public function __construct()
    {

    }

    public function getType()
    {
        return self::TYPE_FLEXPAPER;
    }

    public function getDescription()
    {
        return _('Generates a flexpaper flash file');
    }

    public function getMediaAlchemystSpec()
    {
        if ( ! $this->spec)
        {
            $this->spec = new \MediaAlchemyst\Specification\Flash();
        }

        return $this->spec;
    }

}
