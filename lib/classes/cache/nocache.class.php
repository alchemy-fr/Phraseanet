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
class cache_nocache implements cache_interface
{

  /**
   *
   * @var cache_interface
   */
  protected $cache;
  /**
   *
   * @var array
   */
  protected $datas = array();
  /**
   *
   * @var boolean
   */
  protected $online = false;

  /**
   *
   * @param registryInterface $registry
   * @return cache_nocache
   */
  public function __construct(registryInterface $registry)
  {
    return $this;
  }

  /**
   *
   * @param string $key
   * @param mixed $value
   * @param int $expiration
   * @return cache_nocache
   */
  public function set($key, $value, $expiration)
  {
    $this->datas[$key] = $value;

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    if (!isset($this->datas[$key]))
      throw new Exception('Unable to retrieve the value');

    return $this->datas[$key];
  }

  /**
   *
   * @param string $key
   * @return cache_nocache
   */
  public function delete($key)
  {
    if (isset($this->datas[$key]))
      unset($this->datas[$key]);

    return $this;
  }

  /**
   *
   * @param array $array_keys
   * @return cache_nocache
   */
  public function deleteMulti(Array $array_keys)
  {
    foreach ($array_keys as $key)
      $this->delete($key);

    return $this;
  }

  /**
   *
   * @return array
   */
  public function getStats()
  {
    return array();
  }

  /**
   *
   * @return cache_nocache
   */
  public function flush()
  {
    $this->datas = array();

    return $this;
  }

  /**
   *
   * @return string
   */
  public function get_version()
  {
    return '';
  }

  /**
   *
   * @return boolean
   */
  public function ping()
  {
    return true;
  }

}
