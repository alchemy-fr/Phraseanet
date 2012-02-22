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
class RedisCache extends ServiceAbstract implements ServiceInterface
{

  const DEFAULT_HOST = "localhost";
  const DEFAULT_PORT = "6379";

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

  /**
   *
   * @return Cache\ApcCache
   */
  public function getDriver()
  {
    if (!extension_loaded('redis'))
    {
      throw new \Exception('The Redis cache requires the Redis extension.');
    }

    $redis = new \Redis();

    if (!$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY))
    {
      $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    }

    if ($redis->connect($this->host, $this->port))
    {
      $service = new CacheDriver\RedisCache();
      $service->setRedis($redis);
    }
    else
    {
      throw new \Exception(sprintf("Redis instance with host '%s' and port '%s' is not reachable", $this->host, $this->port));
    }

    $service->setNamespace(md5(realpath(__DIR__.'/../../../../../../')));

    return $service;
  }

  public function getType()
  {
    return 'redis';
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

