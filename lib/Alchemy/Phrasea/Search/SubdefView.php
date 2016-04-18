<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

class SubdefView
{
    /**
     * @var \media_subdef
     */
    private $subdef;

    public function __construct(\media_subdef $subdef)
    {
        $this->subdef = $subdef;
    }

    /**
     * @return \media_subdef
     */
    public function getSubdef()
    {
        return $this->subdef;
    }
}
