<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\ConnectionFactory;
use Alchemy\Phrasea\Cache\Factory;

class FactoryTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideCacheTypes
     */
    public function testFactoryCreate($name, $extension, $expected)
    {
        if (null !== $extension && !extension_loaded($extension)) {
            $this->markTestSkipped(sprintf('Extension %s is not loaded', $extension));
        }

        $factory = new Factory(new ConnectionFactory());
        $this->assertInstanceOf($expected, $factory->create($name, []));
    }

    public function provideCacheTypes()
    {
        return [
            ['apc', 'apc', 'Alchemy\Phrasea\Cache\ApcCache'],
            ['apccache', 'apc', 'Alchemy\Phrasea\Cache\ApcCache'],
            ['array', null, 'Alchemy\Phrasea\Cache\ArrayCache'],
            ['arraycache', null, 'Alchemy\Phrasea\Cache\ArrayCache'],
            ['memcache', 'memcache', 'Alchemy\Phrasea\Cache\MemcacheCache'],
            ['memcachecache', 'memcache', 'Alchemy\Phrasea\Cache\MemcacheCache'],
            ['memcached', 'memcached', 'Alchemy\Phrasea\Cache\MemcachedCache'],
            ['memcachecached', 'memcached', 'Alchemy\Phrasea\Cache\MemcachedCache'],
            ['redis', 'redis', 'Alchemy\Phrasea\Cache\RedisCache'],
            ['rediscache', 'redis', 'Alchemy\Phrasea\Cache\RedisCache'],
            ['wincache', 'wincache', 'Alchemy\Phrasea\Cache\WincacheCache'],
            ['wincachecache', 'wincache', 'Alchemy\Phrasea\Cache\WincacheCache'],
            ['xcache', 'xcache', 'Alchemy\Phrasea\Cache\XcacheCache'],
            ['xcachecache', 'xcache', 'Alchemy\Phrasea\Cache\XcacheCache'],
        ];
    }
}
