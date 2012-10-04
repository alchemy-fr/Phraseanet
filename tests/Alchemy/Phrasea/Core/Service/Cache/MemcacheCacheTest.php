<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class ServiceMemcacheCacheTest extends PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                self::$DI['app'], array()
        );

        if (extension_loaded('memcache')) {
            $service = $cache->getDriver();
            $this->assertTrue($service instanceof \Doctrine\Common\Cache\CacheProvider);
        } else {
            try {
                $cache->getDriver();
                $this->fail("should raise an exception");
            } catch (\Exception $e) {

            }
        }
    }

    public function testServiceException()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                self::$DI['app'], array()
        );

        try {
            $cache->getDriver();
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testType()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                self::$DI['app'], array()
        );

        $this->assertEquals("memcache", $cache->getType());
    }

    public function testHost()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                self::$DI['app'], array()
        );

        $this->assertEquals(\Alchemy\Phrasea\Core\Service\Cache\MemcacheCache::DEFAULT_HOST, $cache->getHost());
    }

    public function testPort()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                self::$DI['app'], array()
        );

        $this->assertEquals(\Alchemy\Phrasea\Core\Service\Cache\MemcacheCache::DEFAULT_PORT, $cache->getPort());
    }
}
