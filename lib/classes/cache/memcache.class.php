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
class cache_memcache implements cache_interface
{

  /**
   *
   * @var cache_interface
   */
  protected $memcache;

  /**
   *
   * @var boolean
   */
  protected $online = false;
  protected $host;
  protected $port;

  /**
   *
   * @param registryInterface $registry
   * @return cache_memcache
   */
  public function __construct(registryInterface $registry)
  {
    if (!extension_loaded('Memcache'))
    {
      throw new Exception('Memcache is not loaded');
    }

    $this->memcache = new Memcache();

    $this->host = $registry->get('GV_cache_server_host');
    $this->port = $registry->get('GV_cache_server_port');

    $this->memcache->addServer($this->host, $this->port);

    if (!$this->memcache->getServerStatus($this->host, $this->port))
      throw new Exception('Unable to connect');

    $this->online = true;

    return $this;
  }

  /**
   *
   * @param string $key
   * @param mixed $value
   * @param int $expiration
   * @return cache_memcache
   */
  public function set($key, $value, $expiration)
  {
    $this->memcache->set($key, $value, 0, $expiration);

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    $value = $this->memcache->get($key);

    if ($value === false)
    {
      throw new Exception('Unable to retrieve the value');
    }

    return $value;
  }

  /**
   *
   * @param string $key
   * @return cache_memcache
   */
  public function delete($key)
  {
    $this->memcache->delete($key);

    return $this;
  }

  /**
   *
   * @param array $array_keys
   * @return cache_memcache
   */
  public function deleteMulti(Array $array_keys)
  {
    foreach ($array_keys as $key)
      $this->memcache->delete($key);

    return $this;
  }

  /**
   *
   * @return array
   */
  public function getStats()
  {
    return array(
        $this->host . ':' . $this->port => $this->memcache->getStats()
    );
  }

  /**
   *
   * @return cache_memcache
   */
  public function flush()
  {
    $this->memcache->flush();

    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_version()
  {
    return $this->memcache->getVersion();
  }

  /**
   *
   * @return boolean
   */
  public function ping()
  {
    return!!$this->online;
  }

}
