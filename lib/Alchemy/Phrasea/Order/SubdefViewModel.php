<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

class SubdefViewModel
{
    /**
     * @var \media_subdef
     */
    private $subdef;

    /**
     * @var \media_Permalink_Adapter
     */
    private $link;

    public function __construct(\media_subdef $subdef, \media_Permalink_Adapter $link)
    {
        $this->subdef = $subdef;
        $this->link = $link;
    }

    /**
     * @return \media_subdef
     */
    public function getSubdef()
    {
        return $this->subdef;
    }

    /**
     * @return \media_Permalink_Adapter
     */
    public function getLink()
    {
        return $this->link;
    }
}
