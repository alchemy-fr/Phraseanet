<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class Bridge_Api_AbstractCollection
{
    /**
     *
     * @var int
     */
    protected $total_page = 1;

    /**
     *
     * @var int
     */
    protected $current_page = 1;

    /**
     *
     * @var int
     */
    protected $total_items;

    /**
     *
     * @var int
     */
    protected $items_per_page;

    /**
     *
     * @var Array
     */
    protected $elements = [];

    /**
     *
     * @return int
     */
    public function get_total_items()
    {
        return $this->total_items;
    }

    public function set_total_items($total_items)
    {
        $this->total_items = (int) $total_items;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_items_per_page()
    {
        return $this->items_per_page;
    }

    /**
     *
     * @param  int                           $items_per_page
     * @return Bridge_Api_AbstractCollection
     */
    public function set_items_per_page($items_per_page)
    {
        $this->items_per_page = (int) $items_per_page;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_current_page()
    {
        return $this->current_page;
    }

    /**
     *
     * @param  int                           $current_page
     * @return Bridge_Api_AbstractCollection
     */
    public function set_current_page($current_page)
    {
        if ($current_page > 0)
            $this->current_page = (int) $current_page;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_total_page()
    {
        return $this->total_page;
    }

    /**
     *
     * @param  int                           $total_page
     * @return Bridge_Api_AbstractCollection
     */
    public function set_total_page($total_page)
    {
        if ($total_page > 0)
            $this->total_page = (int) $total_page;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function has_next_page()
    {
        return $this->current_page < $this->total_page;
    }

    /**
     *
     * @return boolean
     */
    public function has_previous_page()
    {
        return $this->current_page > 1;
    }

    /**
     *
     * @return boolean
     */
    public function has_more_than_one_page()
    {
        return $this->total_page > 1;
    }

    /**
     *
     * @return Array
     */
    public function get_elements()
    {
        return $this->elements;
    }
}
