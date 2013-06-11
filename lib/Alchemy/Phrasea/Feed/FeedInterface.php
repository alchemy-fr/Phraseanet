<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed;

interface FeedInterface
{
    public function getIconUrl();

    public function getTitle();

    public function getEntries($offset_start = 0, $how_many = null);

    public function getSubtitle();

    public function isAggregated();

    public function getCreatedOn();

    public function getUpdatedOn();

    public function hasPage($page, $pageSize);
}
