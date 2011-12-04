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
class cache_memcached implements cache_interface
{

  /**
   *
   * @var cache_interface
   */
  protected $memcached;
  /**
   *
   * @var boolean
   */
  protected $online = false;

  /**
   *
   * @param registryInterface $registry
   * @return cache_memcached
   */
  public function __construct(registryInterface $registry)
  {
    if (!extension_loaded('Memcached'))
    {
      throw new Exception('Memcached is not loaded');
    }

    $this->memcached = new Memcached();

    $host = $registry->get('GV_cache_server_host');
    $port = $registry->get('GV_cache_server_port');

    /**
     * We do not activate binary protocol because if some issues
     *
     * https://code.google.com/p/memcached/issues/detail?id=106
     *
     */

//    $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);

    $this->memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);
    $this->memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 500);
    $this->memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 500);
    $this->memcached->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 1);
    $this->memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
//    @$this->memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);

    $this->memcached->addServer($host, $port);

    $this->memcached->getVersion();
    if ($this->memcached->getResultCode() !== Memcached::RES_SUCCESS)
      throw new Exception('Unable to connect');

    $this->online = true;

    return $this;
  }

  /**
   *
   * @param string $key
   * @param mixed $value
   * @param int $expiration
   * @return cache_memcached
   */
  public function set($key, $value, $expiration)
  {
    $this->memcached->set($key, $value, $expiration);

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    $value = $this->memcached->get($key);

    if ($this->memcached->getResultCode() !== Memcached::RES_SUCCESS)
    {
      throw new Exception('Unable to retrieve the value');
    }

    return $value;
  }

  /**
   *
   * @param string $key
   * @return cache_memcached
   */
  public function delete($key)
  {
    $this->memcached->delete($key);

    return $this;
  }

  /**
   *
   * @param array $array_keys
   * @return cache_memcached
   */
  public function deleteMulti(Array $array_keys)
  {
    foreach ($array_keys as $key)
      $this->memcached->delete($key);

    return $this;
  }

  /**
   *
   * @return array
   */
  public function getStats()
  {
    return $this->memcached->getStats();
  }

  /**
   *
   * @return cache_memcached
   */
  public function flush()
  {
    $this->memcached->flush();

    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_version()
  {
    return $this->memcached->getVersion();
  }

  /**
   *
   * @return boolean
   */
  public function ping()
  {
    return $this->online;
  }

}
