<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Doctrine\Common\Cache\CacheProvider;

class RedisCache extends CacheProvider implements Cache
{
    /**
     * @var \Redis
     */
    private $_redis;

    /**
     * Sets the redis instance to use.
     *
     * @param Redis $redis
     */
    public function setRedis(\Redis $redis)
    {
        $this->_redis = $redis;
    }

    /**
     * Gets the memcache instance used by the cache.
     *
     * @return Redis
     */
    public function getRedis()
    {
        return $this->_redis;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->_redis->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        return (bool) $this->_redis->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if (0 === $lifeTime) {
            return $this->_redis->set($id, $data);
        } else {
            return $this->_redis->setex($id, $lifeTime, $data);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        return $this->_redis->del($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->_redis->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $stats = $this->_redis->info();

        return [
            Cache::STATS_HITS              => false,
            Cache::STATS_MISSES            => false,
            Cache::STATS_UPTIME            => $stats['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE      => $stats['used_memory'],
            Cache::STATS_MEMORY_AVAILIABLE => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isServer()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isOnline()
    {
        return is_array($this->getRedis()->info());
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if ( ! $this->contains($key)) {
            throw new Exception('Unable to retrieve the value');
        }

        return $this->fetch($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return $this;
    }

    public function removeByPattern($pattern)
    {
        $keysToremove = [];
        $iterator = null;
        while(false !== ($keys = $this->_redis->scan($iterator, $pattern))) {
            $keysToremove = array_merge($keysToremove, $keys);
        }

        $this->_redis->del($keysToremove);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'redis';
    }
}
