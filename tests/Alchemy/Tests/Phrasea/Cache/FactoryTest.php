<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideCacheTypes
     */
    public function testFactoryCreate($name, $extension, $expected)
    {
        if (null !== $extension && !extension_loaded($extension)) {
            $this->markTestSkipped(sprintf('Extension %s is not loaded', $extension));
        }

        $factory = new Factory();
        $this->assertInstanceOf($expected, $factory->create($name, array()));
    }

    public function provideCacheTypes()
    {
        return array(
            array('apc', 'apc', 'Alchemy\Phrasea\Cache\ApcCache'),
            array('apccache', 'apc', 'Alchemy\Phrasea\Cache\ApcCache'),
            array('array', null, 'Alchemy\Phrasea\Cache\ArrayCache'),
            array('arraycache', null, 'Alchemy\Phrasea\Cache\ArrayCache'),
            array('memcache', 'memcache', 'Alchemy\Phrasea\Cache\MemcacheCache'),
            array('memcachecache', 'memcache', 'Alchemy\Phrasea\Cache\MemcacheCache'),
            array('memcached', 'memcached', 'Alchemy\Phrasea\Cache\MemcachedCache'),
            array('memcachecached', 'memcached', 'Alchemy\Phrasea\Cache\MemcachedCache'),
            array('redis', 'redis', 'Alchemy\Phrasea\Cache\RedisCache'),
            array('rediscache', 'redis', 'Alchemy\Phrasea\Cache\RedisCache'),
            array('wincache', 'wincache', 'Alchemy\Phrasea\Cache\WincacheCache'),
            array('wincachecache', 'wincache', 'Alchemy\Phrasea\Cache\WincacheCache'),
            array('xcache', 'xcache', 'Alchemy\Phrasea\Cache\XcacheCache'),
            array('xcachecache', 'xcache', 'Alchemy\Phrasea\Cache\XcacheCache'),
        );
    }
}
