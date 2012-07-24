<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class system_url
{
    protected $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function get_url()
    {
        return $this->url;
    }
}

