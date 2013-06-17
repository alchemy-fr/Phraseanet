<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\RSS;

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface FeedXMLRSSImageInterface
{

    public function __construct($url, $title, $link);

    public function getUrl();

    public function getTitle();

    public function getLink();

    public function getDescription();

    public function getHeight();

    public function getWidth();
}
