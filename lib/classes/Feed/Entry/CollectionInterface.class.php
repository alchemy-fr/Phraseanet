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
interface Feed_Entry_CollectionInterface
{
  public function __construct();

  public function add_entry(Feed_Entry_Adapter $entry);

  public function get_entries();
}
