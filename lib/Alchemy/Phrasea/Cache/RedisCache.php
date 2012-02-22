<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Doctrine\Common\Cache\AbstractCache;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class RedisCache extends AbstractCache implements Cache
{

  /**
   * @var Memcache
   */
  private $_redis;

  /**
   * Sets the redis instance to use.
   *
   * @param Redis $memcache
   */
  public function setRedis(\Redis $redis)
  {
    $this->_redis = $redis;
  }

  /**
   * Gets the memcache instance used by the cache.
   *
   * @return Memcache
   */
  public function getRedis()
  {
    return $this->_redis;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds()
  {
    return $this->_redis->keys('*');
  }

  /**
   * {@inheritdoc}
   */
  protected function _doFetch($id)
  {
    return $this->_redis->get($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function _doContains($id)
  {
    return (bool) $this->_redis->get($id);
  }

  /**
   * {@inheritdoc}
   */
  protected function _doSave($id, $data, $lifeTime = 0)
  {
    if (0 === $lifeTime)
    {
      return $this->_redis->set($id, $data);
    }
    else
    {
      return $this->_redis->setex($id, $lifeTime, $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function _doDelete($id)
  {
    return $this->_redis->delete($id);
  }

  public function isServer()
  {
    return true;
  }

  public function getStats()
  {
    return null;
  }

  public function get($key)
  {
    if (!$this->contains($key))
    {
      throw new Exception('Unable to retrieve the value');
    }

    return $this->fetch($key);
  }

  public function deleteMulti(array $array_keys)
  {
    foreach ($array_keys as $id)
    {
      $this->delete($id);
    }

    return $this;
  }

}
