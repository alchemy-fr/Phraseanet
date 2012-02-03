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

use Alchemy\Phrasea\Loader\LoaderStrategy as CacheStrategy;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
Class ApcAutoloader extends Autoloader implements CacheStrategy
{

  private $prefix;

  /**
   * Constructor.
   *
   * @param string $prefix A prefix to create a namespace in APC
   *
   * @api
   */
  public function __construct($prefix)
  {
    if (!$this->isAvailable())
    {
      throw new \Exception("Apc cache is not enable");
    }

    $this->prefix = $prefix;
  }

  /**
   * Finds a file by class name while caching lookups to APC.
   *
   * @param string $class A class name to resolve to file
   */
  public function findFile($class)
  {
    var_dump(__CLASS__ . " find : " . $class);
    if (false === $file = apc_fetch($this->prefix . $class))
    {
      apc_store($this->prefix . $class, $file = parent::findFile($class));
    }

    return $file;
  }

  public function isAvailable()
  {
    return extension_loaded('apc');
  }

  public function register($prepend = false)
  {
    spl_autoload_register(array($this, 'loadClass'), true, $prepend);
  }

}