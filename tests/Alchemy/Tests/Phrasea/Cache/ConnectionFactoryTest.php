<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\ConnectionFactory;
use Alchemy\Phrasea\Cache\Factory;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ConnectionFactoryTest extends \PhraseanetTestCase
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

    public function testGetInvalidRedisConnection()
    {
        $this->expectException(\RedisException::class);
        $this->expectExceptionMessageMatches('/php_network_getaddresses: getaddrinfo/');
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('redis extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $redis = $factory->getRedisConnection(['host' => 'unknown-host', 'port' => 666]);

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

    public function testGetInvalidMemcachedConnection()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Memcached instance with host \'unknown-host\' and port \'666\' is not reachable');
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('memcached extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $memcached = $factory->getMemcachedConnection(['host' => 'unknown-host', 'port' => 666]);

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

    public function testGetInvalidMemcacheConnection()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Memcache instance with host \'unknown-host\' and port \'666\' is not reachable');
        if (!extension_loaded('memcache')) {
            $this->markTestSkipped('memcache extension is not loaded.');
        }

        $factory = new ConnectionFactory();
        $memcache = $factory->getMemcacheConnection(['host' => 'unknown-host', 'port' => 666]);

        $this->assertInstanceOf('Memcache', $memcache);
        $this->assertSame($memcache, $factory->getMemcacheConnection());
    }
}
