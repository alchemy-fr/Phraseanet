<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
interface Feed_Interface
{

    public function get_title();

    public function get_subtitle();

    public function get_created_on();

    public function get_updated_on();

    public function get_entries($offset_start, $how_many);

    public function get_count_total_entries();

    public function get_homepage_link(registryInterface $registry, $format, $page = null);

    public function get_user_link(registryInterface $registry, User_Adapter $user, $format, $page = null, $renew_token = false);

    public function get_icon_url();

    public function is_aggregated();
}
