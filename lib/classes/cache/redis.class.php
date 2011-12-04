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
class cache_redis implements cache_interface
{

  /**
   *
   * @var Redis
   */
  protected $redis;

  protected $igbinary = true;

  /**
   *
   * @param registryInterface $registry
   * @return cache_redis
   */
  public function __construct(registryInterface $registry)
  {
    if (!extension_loaded('Redis'))
    {
      throw new Exception('Redis is not loaded');
    }

    $this->redis = new Redis();
    $this->redis->connect($registry->get('GV_cache_server_host'), $registry->get('GV_cache_server_port'));
    if (!$this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY))
    {
      $this->igbinary = false;
      $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
    }

    return $this;
  }

  /**
   *
   * @param string $key
   * @param mixed $value
   * @param int $expiration
   * @return cache_redis
   */
  public function set($key, $value, $expiration)
  {
    if ($expiration != 0)
      $this->redis->setex($key, $expiration, $value);
    else
      $this->redis->set($key, $value);

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    $tmp = $this->redis->get($key);
    if ($tmp === false && $this->redis->exists($key) === false)
      throw new Exception('Unable to retrieve the value ' . $key);

    return $tmp;
  }

  /**
   *
   * @param string $key
   * @return cache_redis
   */
  public function delete($key)
  {
    $this->redis->delete($key);

    return $this;
  }

  /**
   *
   * @param array $array_keys
   * @return cache_redis
   */
  public function deleteMulti(Array $array_keys)
  {
    $this->redis->delete($array_keys);

    return $this;
  }

  /**
   *
   * @return array
   */
  public function getStats()
  {
    return array('Redis Server ('.($this->igbinary ? 'YES':'NO').' Igbinary)' => $this->redis->info());
  }

  /**
   *
   * @return cache_redis
   */
  public function flush()
  {
    $this->redis->flushAll();

    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_version()
  {
    $infos = $this->getStats();

    if (isset($infos['redis_version']))

      return $infos['redis_version'];

    return false;
  }

  /**
   *
   * @return boolean
   */
  public function ping()
  {
    return ($this->redis->ping() === '+PONG');
  }

}
