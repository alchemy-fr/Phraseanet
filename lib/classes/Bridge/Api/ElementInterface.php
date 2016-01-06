<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface Bridge_Api_ElementInterface
{

    /**
     * @return int
     */
    public function get_duration();

    public function get_view_count();

    public function get_rating();

    /**
     * @return string
     */
    public function get_id();

    public function get_url();

    public function get_thumbnail();

    public function get_title();

    public function get_description();

    public function get_category();

    public function get_type();

    /**
     * @return Datetime
     */
    public function get_updated_on();

    public function get_created_on();

    /**
     * @return boolean
     */
    public function is_private();
}
