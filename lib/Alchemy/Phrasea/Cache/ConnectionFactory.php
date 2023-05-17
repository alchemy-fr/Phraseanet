<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Exception\RuntimeException;

class ConnectionFactory
{
    private $connections = [];

    /**
     * Returns a Redis connection.
     *
     * @param array $options Available options are 'host' and 'port'
     *
     * @return \Redis
     *
     * @throws RuntimeException
     */
    public function getRedisConnection(array $options = [])
    {
        $options = array_replace(['host' => 'redis', 'port' => 6379], $options);
        if (null !== $cache = $this->getConnection('redis', $options)) {
            return $cache;
        }

        if (!extension_loaded('redis')) {
            throw new RuntimeException('The Redis cache requires the Redis extension.');
        }

        $redis = new \Redis();

        if (!@$redis->connect($options['host'], $options['port'])) {
            throw new RuntimeException(sprintf("Redis instance with host '%s' and port '%s' is not reachable.", $options['host'], $options['port']));
        }
        if (!defined('Redis::SERIALIZER_IGBINARY') || !$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY)) {
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }

        return $this->setConnection('redis', $options, $redis);
    }

    /**
     * Returns a Memcache connection.
     *
     * @param array $options Available options are 'host' and 'port'
     *
     * @return \Memcache
     *
     * @throws RuntimeException
     */
    public function getMemcacheConnection(array $options = [])
    {
        $options = array_replace(['host' => 'localhost', 'port' => 11211], $options);
        if (null !== $cache = $this->getConnection('memcache', $options)) {
            return $cache;
        }

        if (!extension_loaded('memcache')) {
            throw new RuntimeException('The Memcache cache requires the Memcache extension.');
        }

        $memcache = new \Memcache();
        $memcache->addServer($options['host'], $options['port']);

        $key = sprintf("%s:%s", $options['host'], $options['port']);
        $stats = @$memcache->getExtendedStats();

        if (!isset($stats[$key]) || false === $stats[$key]) {
            throw new RuntimeException(sprintf("Memcache instance with host '%s' and port '%s' is not reachable.", $options['host'], $options['port']));
        }

        return $this->setConnection('memcache', $options, $memcache);
    }

    /**
     * Returns a Memcached connection.
     *
     * @param array $options Available options are 'host' and 'port'
     *
     * @return \Memcached
     *
     * @throws RuntimeException
     */
    public function getMemcachedConnection(array $options = [])
    {
        $options = array_replace(['host' => 'localhost', 'port' => 11211], $options);
        if (null !== $cache = $this->getConnection('memcached', $options)) {
            return $cache;
        }

        if (!extension_loaded('memcached')) {
            throw new RuntimeException('The Memcached cache requires the Memcached extension.');
        }

        $memcached = new \Memcached();
        $memcached->addServer($options['host'], $options['port']);
        $memcached->getStats();

        if (\Memcached::RES_SUCCESS !== $memcached->getResultCode()) {
            throw new RuntimeException(sprintf("Memcached instance with host '%s' and port '%s' is not reachable.", $options['host'], $options['port']));
        }

        return $this->setConnection('memcached', $options, $memcached);
    }

    private function setConnection($name, array $options, $value)
    {
        return $this->connections[$this->generateConnectionHash($name, $options)] = $value;
    }

    private function getConnection($name, array $options)
    {
        $hash = $this->generateConnectionHash($name, $options);

        return isset($this->connections[$hash]) ? $this->connections[$hash] : null;
    }

    private function generateConnectionHash($name, array $options)
    {
        return sprintf('%s-%s', $name, md5(serialize($options)));
    }
}
