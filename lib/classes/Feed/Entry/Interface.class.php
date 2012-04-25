<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
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
interface Feed_Entry_Interface
{

    public function __construct(appbox &$appbox, Feed_Adapter &$feed, $id);

    public function get_feed();

    public function get_id();

    public function get_title();

    public function get_subtitle();

    public function set_title($title);

    public function set_subtitle($subtitle);

    public function set_author_name($author_name);

    public function set_author_email($author_email);

    public function get_publisher();

    public function get_created_on();

    public function get_updated_on();

    public function get_author_name();

    public function get_author_email();

    public function get_content();

    public function delete();

    public static function create(appbox &$appbox, Feed_Adapter $feed
    , Feed_Publisher_Adapter $publisher, $title, $subtitle, $author_name, $author_mail);

    public static function load_from_id(appbox $appbox, $id);
}
