<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface Feed_Publisher_Interface
{

    public function __construct(Application $app, $id);

    public function get_user();

    public function is_owner();

    public function get_created_on();

    public function get_added_by();

    public function get_id();

    public function delete();

    public static function create(Application $app, User_Adapter $user, Feed_Adapter $feed, $owner);

    public static function getPublisher(appbox $appbox, Feed_Adapter $feed, User_Adapter $user);
}
