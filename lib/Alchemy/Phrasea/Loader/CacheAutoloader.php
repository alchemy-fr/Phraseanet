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

/**
 * Loop throught op cache code adapter to cache autoloading
 * OpCache code available are apc et xcache
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class CacheAutoloader extends Autoloader
{

  /**
   * Array of all cache adapters
   * @var type
   */
  private $cacheAdapters = array(
      'Apc',
      'Xcache'
  );

  /**
   * The cache adapater
   * @var type
   */
  private $cacheAdapter;

  /**
   * The prefix used to store id's in cache
   * @var string
   */
  private $prefix;

  /**
   * Take a identifier cache key prefix
   * @param string $prefix
   * @throws \Exceptionwhen none of the op cache code are available
   */
  public function __construct($prefix, $namespace = null)
  {
    parent::__construct();

    $this->prefix = $prefix;

    foreach ($this->cacheAdapters as $className)
    {
      $file = sprintf("%s/%sAutoloader.php", __DIR__, $className);

      if (!file_exists($file))
      {
        continue;
      }

      require_once $file;

      $className = sprintf("\Alchemy\Phrasea\Loader\%sAutoloader", $className);

      if (!class_exists($className))
      {
        continue;
      }

      $method = new $className();
      
      if($namespace)
      {
        $method->setNamespace($namespace);
      }
      
      if ($method instanceof LoaderStrategy && $method->isAvailable())
      {
        $this->cacheAdapter = $method;
        break;
      }
    }

    if (null === $this->cacheAdapter)
    {
      throw new \Exception('No Cache available');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findFile($class)
  {
    $file = $this->cacheAdapter->fetch($this->prefix . $class);

    if (false === $file)
    {
      $this->cacheAdapter->save($this->prefix . $class, $file = parent::findFile($class));
    }

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  public function register($prepend = false)
  {
    spl_autoload_register(array($this, 'loadClass'), true, $prepend);
  }

  /**
   * Get the current cache Adapter
   * @return LoaderStrategy
   */
  public function getAdapter()
  {
    return $this->cacheAdapter;
  }

  /**
   * Get the identifier cache key prefix
   * @return string
   */
  public function getPrefix()
  {
    return $this->prefix;
  }


}
