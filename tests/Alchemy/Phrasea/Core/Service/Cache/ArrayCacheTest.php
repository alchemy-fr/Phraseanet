<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class ServiceArrayCacheTest extends PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
                self::$core, array()
        );

        $service = $cache->getDriver();
        $this->assertTrue($service instanceof \Doctrine\Common\Cache\CacheProvider);
    }

    public function testServiceException()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
                self::$core, array()
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
                self::$core, array()
        );

        $this->assertEquals("array", $cache->getType());
    }
}
