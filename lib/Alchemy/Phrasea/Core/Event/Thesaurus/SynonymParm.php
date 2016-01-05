<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Thesaurus;

/**
 * Class SynonymParm
 * @package Alchemy\Phrasea\Core\Event\Thesaurus
 *
 * a small object passed as parameter to thesaurus events.
 * usefull when the synonym does not exists anymore in the thesaurus (after deletion)
 */
class SynonymParm
{
    private $value;     // as the <sy> 'v' attribute in xml
    private $lng;       // the 'lng' attribute

    public function __construct($value, $lng)
    {
        $this->value = $value;
        $this->lng = $lng;
    }

    /**
     * @return \string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return \string
     */
    public function getLng()
    {
        return $this->lng;
    }
}
