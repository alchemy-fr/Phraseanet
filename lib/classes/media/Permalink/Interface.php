<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

interface media_Permalink_Interface
{

    public function get_url();

    public function get_page();

    public function get_id();

    public function get_token();

    public function get_is_activated();

    public function get_created_on();

    public function get_last_modified();

    public function get_label();

    public function set_is_activated($is_activated);

    public function set_label($label);

    public static function getPermalink(Application $app, databox $databox, media_subdef $media_subdef);

    public static function create(Application $app, databox $databox, media_subdef $media_subdef);
}
