<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Cache\ApcCache;
use Alchemy\Phrasea\Cache\ArrayCache;
use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\Cache\MemcacheCache;
use Alchemy\Phrasea\Cache\RedisCache;
use Alchemy\Phrasea\Cache\WinCacheCache;
use Alchemy\Phrasea\Cache\XcacheCache;
use Alchemy\Phrasea\Exception\RuntimeException;

class Factory
{
    /**
     * @param type $name
     * @param type $options
     *
     * @return Cache
     *
     * @throws RuntimeException
     */
    public function create($name, $options)
    {
        switch (strtolower($name)) {
            case 'apc':
            case 'apccache':
                $cache = $this->createApc($options);
                break;
            case 'array':
            case 'arraycache':
                $cache = new ArrayCache();
                break;
            case 'memcache':
            case 'memcachecache':
                $cache = $this->createMemcache($options);
                break;
            case 'memcached':
            case 'memcachecached':
                $cache = $this->createMemcached($options);
                break;
            case 'redis':
            case 'rediscache':
                $cache = $this->createRedis($options);
                break;
            case 'wincache':
            case 'wincachecache':
                $cache = $this->createWincache($options);
                break;
            case 'xcache':
            case 'xcachecache':
                $cache = $this->createXcache($options);
                break;
            default:
                throw new RuntimeException(sprintf('Unnown cache type %s', $name));
        }

        return $cache;
    }

    private function createXcache($options)
    {
        if (!extension_loaded('xcache')) {
            throw new RuntimeException('The XCache cache requires the XCache extension.');
        }

        return new XcacheCache();
    }

    private function createWincache($options)
    {
        if (!extension_loaded('wincache')) {
            throw new RuntimeException('The WinCache cache requires the WinCache extension.');
        }

        return new WinCacheCache();
    }

    private function createRedis($options)
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('The Redis cache requires the Redis extension.');
        }

        $redis = new \Redis();

        $host = isset($options['host']) ? $options['host'] : 'localhost';
        $port = isset($options['port']) ? $options['port'] : 6379;

        if (!$redis->connect($host, $port)) {
            throw new RuntimeException(sprintf("Redis instance with host '%s' and port '%s' is not reachable", $host, $port));
        }
        if (!defined('Redis::SERIALIZER_IGBINARY') || !$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY)) {
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }

        $cache = new RedisCache();
        $cache->setRedis($redis);

        return $cache;
    }

    private function createMemcache($options)
    {
        if (!extension_loaded('memcache')) {
            throw new RuntimeException('The Memcache cache requires the Memcache extension.');
        }

        $host = isset($options['host']) ? $options['host'] : 'localhost';
        $port = isset($options['port']) ? $options['port'] : 11211;

        $memcache = new \Memcache();
        $memcache->addServer($host, $port);

        $key = sprintf("%s:%s", $host, $port);
        $stats = @$memcache->getExtendedStats();

        if (!isset($stats[$key]) || false === $stats[$key]) {
            throw new RuntimeException(sprintf("Memcache instance with host '%s' and port '%s' is not reachable", $host, $port));
        }

        $cache = new MemcacheCache();
        $cache->setMemcache($memcache);

        return $cache;
    }

    private function createMemcached($options)
    {
        if (!extension_loaded('memcached')) {
            throw new RuntimeException('The Memcached cache requires the Memcached extension.');
        }

        $host = isset($options['host']) ? $options['host'] : 'localhost';
        $port = isset($options['port']) ? $options['port'] : 11211;

        $memcached = new \Memcached();
        $memcached->addServer($host, $port);
        $memcached->getStats();

        if (\Memcached::RES_SUCCESS !== $memcached->getResultCode()) {
            throw new RuntimeException(sprintf("Memcached instance with host '%s' and port '%s' is not reachable", $host, $port));
        }

        $cache = new MemcachedCache();
        $cache->setMemcached($memcached);

        return $cache;
    }

    private function createApc($options)
    {
        if (!extension_loaded('apc')) {
            throw new RuntimeException('The APC cache requires the APC extension.');
        }

        return new ApcCache();
    }
}
