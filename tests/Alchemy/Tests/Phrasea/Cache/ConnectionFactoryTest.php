<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\ConnectionFactory;
use Alchemy\Phrasea\Cache\Factory;

class ConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRedisConnection()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('redis extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $redis = $factory->getRedisConnection();

        $this->assertInstanceOf('Redis', $redis);
        $this->assertSame($redis, $factory->getRedisConnection());
    }

    /**
     * @expectedException Alchemy\PHrasea\Exception\RuntimeException
     * @expectedExceptionMessage Redis instance with host 'unknown-host' and port '666' is not reachable
     */
    public function testGetInvalidRedisConnection()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('redis extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $redis = $factory->getRedisConnection(array('host' => 'unknown-host', 'port' => 666));

        $this->assertInstanceOf('Redis', $redis);
        $this->assertSame($redis, $factory->getRedisConnection());
    }

    public function testGetMemcachedConnection()
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('memcached extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $memcached = $factory->getMemcachedConnection();

        $this->assertInstanceOf('Memcached', $memcached);
        $this->assertSame($memcached, $factory->getMemcachedConnection());
    }

    /**
     * @expectedException Alchemy\PHrasea\Exception\RuntimeException
     * @expectedExceptionMessage Memcached instance with host 'unknown-host' and port '666' is not reachable
     */
    public function testGetInvalidMemcachedConnection()
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('memcached extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $memcached = $factory->getMemcachedConnection(array('host' => 'unknown-host', 'port' => 666));

        $this->assertInstanceOf('Memcached', $memcached);
        $this->assertSame($memcached, $factory->getMemcachedConnection());
    }

    public function testGetMemcacheConnection()
    {
        if (!extension_loaded('memcache')) {
            $this->markTestSkipped('memcache extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $memcache = $factory->getMemcacheConnection();

        $this->assertInstanceOf('Memcache', $memcache);
        $this->assertSame($memcache, $factory->getMemcacheConnection());
    }

    /**
     * @expectedException Alchemy\PHrasea\Exception\RuntimeException
     * @expectedExceptionMessage Memcache instance with host 'unknown-host' and port '666' is not reachable
     */
    public function testGetInvalidMemcacheConnection()
    {
        if (!extension_loaded('memcache')) {
            $this->markTestSkipped('memcache extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $memcache = $factory->getMemcacheConnection(array('host' => 'unknown-host', 'port' => 666));

        $this->assertInstanceOf('Memcache', $memcache);
        $this->assertSame($memcache, $factory->getMemcacheConnection());
    }
}
