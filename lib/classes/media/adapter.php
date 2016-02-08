<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Guzzle\Http\Url;

class media_adapter extends media_abstract
{
    /**
     * Constructor
     *
     * Enforces Url to de defined
     *
     * @param int $width
     * @param int $height
     * @param Url $url
     */
    public function __construct($width, $height, Url $url)
    {
        parent::__construct($width, $height, $url);
    }
}
