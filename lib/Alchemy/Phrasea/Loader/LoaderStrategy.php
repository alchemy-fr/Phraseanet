<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Loader;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface LoaderStrategy
{

  /**
   * Check wether the cacheAdapter is available
   * @Return boolean 
   */
  public function isAvailable();

  /**
   *  Get value identified by key from cache
   * @param int $key
   *  @Return boolean
   */
  public function fetch($key);

  /**
   *  Save value identified by key in cache
   * @param int $key
   * @param string $file
   *  @Return boolean
   */
  public function save($key, $file);
}
