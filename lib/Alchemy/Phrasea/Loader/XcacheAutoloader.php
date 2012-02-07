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

require_once __DIR__ . '/LoaderStrategy.php';

use Alchemy\Phrasea\Loader\LoaderStrategy as CacheStrategy;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
Class XcacheAutoloader extends Autoloader implements CacheStrategy
{

  /**
   * {@inheritdoc}
   */
  public function isAvailable()
  {
    return extension_loaded('xcache');
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($key)
  {
    return xcache_get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function save($key, $file)
  {
    return xcache_set($key, $file);
  }

}
