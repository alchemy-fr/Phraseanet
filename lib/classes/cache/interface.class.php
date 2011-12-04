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
 * @package     cache
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface cache_interface
{
  public function set($key, $value, $expiration);

  public function get($key);

  public function delete($key);

  public function deleteMulti(Array $array_keys);

  public function getStats();

  public function flush();

  public function get_version();

  public function ping();
}
