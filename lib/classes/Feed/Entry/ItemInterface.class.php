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
interface Feed_Entry_ItemInterface
{

    public function __construct(appbox &$appbox, Feed_Entry_Adapter &$entry, $id);

    public function get_id();

    public function get_record();

    public function get_ord();

    public function delete();

    public static function create(appbox &$appbox, Feed_Entry_Adapter &$entry, record_adapter &$record);
}
