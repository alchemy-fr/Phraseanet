<?php

namespace Alchemy\Tests\Phrasea\Core\Service\Cache;

use Alchemy\Phrasea\Core\Service\Cache\XcacheCache;

class ServiceXcacheCacheTest extends \PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\XcacheCache(
                self::$DI['app'], array()
        );

        if (extension_loaded('xcache')) {
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
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\XcacheCache(
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
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\XcacheCache(
                self::$DI['app'], array()
        );

        $this->assertEquals("xcache", $cache->getType());
    }
}
