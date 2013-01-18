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
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface Feed_XML_RSS_ImageInterface
{

    public function __construct($url, $title, $link);

    public function get_url();

    public function get_title();

    public function get_link();

    public function get_description();

    public function get_height();

    public function get_width();
}
