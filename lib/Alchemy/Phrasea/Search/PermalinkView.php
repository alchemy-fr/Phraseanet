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

class PermalinkView
{
    /**
     * @var \media_Permalink_Adapter
     */
    private $permalink;

    public function __construct(\media_Permalink_Adapter $permalink)
    {
        $this->permalink = $permalink;
    }

    /**
     * @return \media_Permalink_Adapter
     */
    public function getPermalink()
    {
        return $this->permalink;
    }
}
