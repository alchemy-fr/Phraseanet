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

require_once __DIR__ . '/Autoloader.php';
require_once __DIR__ . '/LoaderStrategy.php';
require_once __DIR__ . '/ApcAutoloader.php';
require_once __DIR__ . '/XcacheAutoloader.php';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class CacheAutoloader extends Autoloader
{

  private $cacheStrategies = array(
      '\Alchemy\Phrasea\Loader\ApcAutoloader',
      '\Alchemy\Phrasea\Loader\XcacheAutoloader',
  );
  private $cache;

  public function __construct($prefix)
  {
    foreach ($this->cacheStrategies as $className)
    {
      $method = new $className($prefix);

      if ($method instanceof LoaderStrategy && $method->isAvailable())
      {
        $this->cache = $method;
        break;
      }
    }

    if (null === $this->cache)
    {
      throw new Exception('No Cache available');
    }
  }

  public function register($prepend = false)
  {
    $this->cache->register($prepend);
  }

}
