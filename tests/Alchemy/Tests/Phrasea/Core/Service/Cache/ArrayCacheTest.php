<?php

namespace Alchemy\Tests\Phrasea\Core\Service\Cache;

use Alchemy\Phrasea\Core\Service\Cache\ArrayCache;

class ServiceArrayCacheTest extends \PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
                self::$DI['app'], array()
        );

        $service = $cache->getDriver();
        $this->assertTrue($service instanceof \Doctrine\Common\Cache\CacheProvider);
    }

    public function testServiceException()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
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
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
                self::$DI['app'], array()
        );

        $this->assertEquals("array", $cache->getType());
    }
}
