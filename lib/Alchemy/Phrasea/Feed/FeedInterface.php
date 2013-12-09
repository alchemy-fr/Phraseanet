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
    /**
     * Returns a boolean indicating whether the feed has a custom icon.
     *
     * @return boolean
     */
    public function getIconUrl();

    /**
     * Returns an UTF-8 title for the feed.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Returns a collection of FeedEntry.
     *
     * @param integer $offset_start
     * @param integer $how_many
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEntries($offset_start = 0, $how_many = null);

    /**
     * Returns an UTF-8 subtitle for the feed.
     *
     * @return string
     */
    public function getSubtitle();

    /**
     * Returns a boolean indicating whether the feed is aggregated or not.
     *
     * @return boolean
     */
    public function isAggregated();

    /**
     * Returns the date of creation of the feed.
     *
     * @return \DateTime
     */
    public function getCreatedOn();

    /**
     * Returns the date of last update of the feed.
     *
     * @return \DateTime
     */
    public function getUpdatedOn();

    /**
     * Returns a boolean indicating whether the feed has a given page.
     *
     * @param integer $pageNumber
     * @param integer $nbEntriesByPage
     *
     * @return \DateTime
     */
    public function hasPage($pageNumber, $nbEntriesByPage);
}
