<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class ServiceApcCacheTest extends PhraseanetPHPUnitAbstract
{

    public function testService()
    {
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
                self::$DI['app'], array()
        );

        if (extension_loaded('apc')) {
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
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
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
        $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
                self::$DI['app'], array()
        );

        $this->assertEquals("apc", $cache->getType());
    }
}
