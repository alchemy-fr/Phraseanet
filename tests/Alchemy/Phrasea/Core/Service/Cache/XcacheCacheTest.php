<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class ServiceXcacheCacheTest extends PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\XcacheCache(
                self::$core, array()
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
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\XcacheCache(
                self::$core, array()
        );

        $this->assertEquals("xcache", $cache->getType());
    }
}
