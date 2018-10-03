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

use Alchemy\Phrasea\Exception\RuntimeException;

class Factory
{
    private $connectionFactory;

    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

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
                $cache = $this->createApc();
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
            case 'memcachedcache':
                $cache = $this->createMemcached($options);
                break;
            case 'redis':
            case 'rediscache':
                $cache = $this->createRedis($options);
                break;
            case 'wincache':
            case 'wincachecache':
                $cache = $this->createWincache();
                break;
            case 'xcache':
            case 'xcachecache':
                $cache = $this->createXcache();
                break;
            default:
                throw new RuntimeException(sprintf('Unnown cache type %s', $name));
        }

        return $cache;
    }

    private function createXcache()
    {
        if (!extension_loaded('xcache')) {
            throw new RuntimeException('The XCache cache requires the XCache extension.');
        }

        return new XcacheCache();
    }

    private function createWincache()
    {
        if (!extension_loaded('wincache')) {
            throw new RuntimeException('The WinCache cache requires the WinCache extension.');
        }

        return new WinCacheCache();
    }

    private function createRedis($options)
    {
        $redis = $this->connectionFactory->getRedisConnection($options);

        $cache = new RedisCache();
        $cache->setRedis($redis);

        return $cache;
    }

    private function createMemcache($options)
    {
        $memcache = $this->connectionFactory->getMemcacheConnection($options);

        $cache = new MemcacheCache();
        $cache->setMemcache($memcache);

        return $cache;
    }

    private function createMemcached($options)
    {
        $memcached = $this->connectionFactory->getMemcachedConnection($options);

        $cache = new MemcachedCache();
        $cache->setMemcached($memcached);

        return $cache;
    }

    private function createApc()
    {
        if (!extension_loaded('apc')) {
            throw new RuntimeException('The APC cache requires the APC extension.');
        }

        return new ApcCache();
    }
}
