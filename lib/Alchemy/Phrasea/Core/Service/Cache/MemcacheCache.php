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
    Alchemy\Phrasea\Cache as CacheDriver;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MemcacheCache extends ServiceAbstract implements ServiceInterface
{

  const DEFAULT_HOST = "localhost";
  const DEFAULT_PORT = "11211";

  protected $host;
  protected $port;

  public function __construct(Core $core, $name, Array $options)
  {
    parent::__construct( $core, $name, $options);

    $this->host = isset($options["host"]) ? $options["host"] : self::DEFAULT_HOST;

    $this->port = isset($options["port"]) ? $options["port"] : self::DEFAULT_PORT;
  }

  public function getScope()
  {
    return 'cache';
  }

  public function getDriver()
  {
    if (!extension_loaded('memcache'))
    {
      throw new \Exception('The Memcache cache requires the Memcache extension.');
    }

    $memcache = new \Memcache();

    $memcache->addServer($this->host, $this->port);

    $key = sprintf("%s:%s", $this->host, $this->port);

    $stats = @$memcache->getExtendedStats();

    if (isset($stats[$key]))
    {
      $service = new CacheDriver\MemcacheCache();
      $service->setMemcache($memcache);
    }
    else
    {
      throw new \Exception(sprintf("Memcache instance with host '%s' and port '%s' is not reachable", $this->host, $this->port));
    }

    $service->setNamespace(md5(realpath(__DIR__.'/../../../../../../')));

    return $service;
  }

  public function getType()
  {
    return 'memcache';
  }

  public function getHost()
  {
    return $this->host;
  }

  public function getPort()
  {
    return $this->port;
  }

  public static function getMandatoryOptions()
  {
    return array('host', 'port');
  }

}

