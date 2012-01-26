<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Cache;

use Alchemy\Phrasea\Core,
    Alchemy\Phrasea\Core\Service,
    Alchemy\Phrasea\Core\Service\ServiceAbstract,
    Alchemy\Phrasea\Core\Service\ServiceInterface,
    Alchemy\Phrasea\Cache as PhraseaCache;
use Doctrine\Common\Cache as CacheService;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MemcachedCache extends ServiceAbstract implements ServiceInterface
{

  const DEFAULT_HOST = "localhost";
  const DEFAULT_PORT = "11211";

  protected $host;
  protected $port;

  public function __construct($name, Array $options, Array $dependencies)
  {
    parent::__construct($name, $options, $dependencies);

    $this->host = isset($options["host"]) ? $options["host"] : self::DEFAULT_HOST;

    $this->port = isset($options["port"]) ? $options["port"] : self::DEFAULT_PORT;
  }

  public function getScope()
  {
    return 'cache';
  }

  /**
   *
   * @return Cache\ApcCache
   */
  public function getService()
  {
    if (!extension_loaded('memcached'))
    {
      throw new \Exception('The Memcached cache requires the Memcached extension.');
    }

    $memcached = new \Memcached();

    $memcached->addServer($this->host, $this->port);

    $memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 500);
    $memcached->setOption(\Memcached::OPT_SEND_TIMEOUT, 500);
    $memcached->setOption(\Memcached::OPT_RECV_TIMEOUT, 500);
    $memcached->setOption(\Memcached::OPT_SERVER_FAILURE_LIMIT, 1);
    $memcached->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);

    if ($memcached->getVersion())
    {
      $service = new PhraseaCache\MemcachedCache();
      $service->setMemcached($memcached);
    }
    else
    {
      $service = new CacheService\ArrayCache();
    }

    $registry = $this->getRegistry();

    $service->setNamespace($registry->get("GV_sit", ""));

    return $service;
  }

  public function getType()
  {
    return 'memcached';
  }

  public function getHost()
  {
    return $this->host;
  }

  public function getPort()
  {
    return $this->port;
  }

  private function getRegistry()
  {
    $registry = $this->getDependency("registry");

    if (!$registry instanceof \registryInterface)
    {
      throw new \Exception(sprintf('Registry dependency does not implement registryInterface for %s service', $this->name));
    }

    return $registry;
  }

}

