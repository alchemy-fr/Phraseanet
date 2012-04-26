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
class Feed_Entry_Collection implements Feed_Entry_CollectionInterface
{
    /**
     *
     * @var array
     */
    protected $entries = array();

    /**
     *
     * @var DateTime
     */
    protected $updated_on;

    /**
     *
     * @var string
     */
    protected $title;

    /**
     *
     * @var string
     */
    protected $subtitle;

    /**
     *
     * @return Feed_Entry_Collection
     */
    public function __construct()
    {
        return $this;
    }

    /**
     *
     * @param Feed_Entry_Adapter $entry
     * @return Feed_Entry_Collection
     */
    public function add_entry(Feed_Entry_Adapter $entry)
    {
        $this->entries[] = $entry;

        return $this;
    }

    /**
     *
     * @return Array
     */
    public function get_entries()
    {
        return $this->entries;
    }
}
