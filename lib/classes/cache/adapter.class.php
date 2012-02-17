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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class cache_adapter implements cache_interface
{

  /**
   *
   * @var Array
   */
  protected $adapters = array(
      'nocache' => array(
          'cache_nocache'
      ),
      'memcached' => array(
          'cache_memcache'
      ),
      'redis' => array(
          'cache_redis'
      )
  );

  /**
   *
   * @var cache_interface
   */
  protected $cache;

  /**
   *
   * @var cache
   */
  protected static $_instance;

  /**
   *
   * @var boolean
   */
  protected static $_loaded;

  /**
   *
   * @var string
   */
  protected $current_adapter;

  /**
   *
   * @var string
   */
  protected $prefix;

  /**
   *
   * @param registryInterface $registry
   * @param string $type
   * @return cache_adapter
   */
  protected function __construct(registryInterface $registry)
  {
    $type = $registry->get('GV_cache_server_type');

    if (trim($type) === '')
      $type = 'nocache';

    $this->prefix = $registry->get('GV_sit');

    if (self::$_loaded === true)
      throw new Exception('Already tried to load, no adapters');

    if (!isset($this->adapters[$type]))
      throw new Exception(sprintf('Unknow cache type %s', $type));

    $loaded = false;
    $n = 0;

    while (!$loaded && $n < count($this->adapters[$type]))
    {
      try
      {
        $this->cache = new $this->adapters[$type][$n]($registry);
        $this->current_adapter = $this->adapters[$type][$n];
        $this->cache->ping();
        $loaded = true;
      }
      catch (Exception $e)
      {
        $this->cache = $this->current_adapter = null;
        $n++;
        unset($e);
      }
    }

    if (!($this->cache instanceof cache_interface))
    {
      $this->current_adapter = 'nocache';
      $this->cache = new cache_nocache($registry);
    }

    self::$_loaded = true;

    return $this;
  }

  /**
   *
   * @param registryInterface $registry
   * @param string $type
   * @return cache_adapter
   */
  public static function get_instance(registryInterface $registry)
  {
    if (!self::$_instance instanceof self)
      self::$_instance = new self($registry);

    return self::$_instance;
  }

  /**
   *
   * @return string
   */
  public function get_current_adpapter()
  {
    return $this->current_adapter;
  }

  /**
   *
   * @param <type> $key
   * @param <type> $value
   * @param <type> $expiration
   * @return boolean
   */
  public function set($key, $value, $expiration = 0)
  {
    try
    {
      return $this->cache->set($this->generate_key($key), $value, $expiration);
      return true;
    }
    catch (Exception $e)
    {
      unset($e);

      return false;
    }
  }

  protected function generate_key($key)
  {
    if (is_string($key))
    {
      return md5($this->prefix . ' ' . $key);
    }
    if (is_array($key))
    {
      $ret = array();
      foreach ($key as $k => $v)
      {
        $ret[$k] = md5($this->prefix . ' ' . $v);
      }

      return $ret;
    }
  }

  /**
   *
   * @param <type> $key
   * @return <type>
   */
  public function get($key)
  {
    $tmp = $this->cache->get($this->generate_key($key));

    return $tmp;
  }

  /**
   *
   * @param <type> $key
   * @return <type>
   */
  public function delete($key)
  {
    return $this->cache->delete($this->generate_key($key));
  }

  /**
   *
   * @param array $array_keys
   * @return <type>
   */
  public function deleteMulti(Array $array_keys)
  {
    return $this->cache->deleteMulti($this->generate_key($array_keys));
  }

  /**
   *
   * @return <type>
   */
  public function getStats()
  {
    return $this->cache->getStats();
  }

  /**
   *
   * @return <type>
   */
  public function flush()
  {
    return $this->cache->flush();
  }

  /**
   *
   * @return <type>
   */
  public function get_version()
  {
    return $this->cache->getVersion();
  }

  /**
   *
   * @return boolean
   */
  public function ping()
  {
    return $this->cache->ping();
  }

  /**
   *
   * @param string $message
   * @return cache_adapter
   */
  protected function log($message)
  {
    $registry = registry::get_instance();
    $date = new DateTime();
    $message = $date->format(DATE_ATOM) . " $message \n";
    $filename = $registry->get('GV_RootPath') . 'logs/cache.log';
    file_put_contents($filename, $message, FILE_APPEND);

    return $this;
  }

}
