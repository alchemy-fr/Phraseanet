<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Psr\Http\Message\UriInterface;

class media_adapter extends media_abstract
{
    /**
     * Constructor
     *
     * Enforces Url to de defined
     *
     * @param int $width
     * @param int $height
     * @param UriInterface $url
     */
    public function __construct($width, $height, UriInterface $url)
    {
        parent::__construct($width, $height, $url);
    }
}
