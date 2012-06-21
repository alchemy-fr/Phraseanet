<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class DoctrineTest extends PhraseanetPHPUnitAbstract
{
    protected $options;

    public function setUp()
    {
        parent::setUp();
        $this->options = array(
            "debug" => false
            , "log"   => array('service' => "Log\\sql_logger")
            , "dbal"    => "main_connexion"
            , "cache"   => array(
                "metadata" => array('service' => "Cache\\array_cache")
                , "query"   => array('service' => "Cache\\array_cache")
                , "result"  => array('service' => "Cache\\array_cache")
            )
        );
    }

    public function testService()
    {
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                self::$core, $this->options
        );

        $this->assertInstanceOf("\Doctrine\ORM\EntityManager", $doctrine->getDriver());
    }

    public function testType()
    {
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                self::$core, $this->options
        );

        $this->assertEquals("doctrine", $doctrine->getType());
    }

    public function testExceptionMissingOptions()
    {
        try {
            $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    self::$core, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testNoCacheInOptions()
    {
        $this->markTestSkipped('To rewrite');
        unset($this->options["cache"]);
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                self::$core, $this->options
        );

        foreach ($doctrine->getCacheServices()->all() as $service) {
            $this->assertEquals("array", $service->getType());
        }
    }

    public function testUnknowCache()
    {
        $this->options["cache"]["result"] = "unknowCache";

        try {
            $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    self::$core, $this->options
            );
            $this->fail("An exception should be raised");
        } catch (\Exception $e) {

        }
    }

    public function testIsDebug()
    {
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                self::$core, $this->options
        );

        $this->assertFalse($doctrine->isDebug());

        $this->options['debug'] = true;
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                self::$core, $this->options
        );

        $this->assertTrue($doctrine->isDebug());
    }

    public function testGetCacheServices()
    {
        $this->markTestSkipped('To rewrite');
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                self::$core, $this->options
        );
        $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag"
            , $doctrine->getCacheServices());

        foreach ($doctrine->getCacheServices()->all() as $service) {
            $this->assertEquals("array", $service->getType());
        }

        $this->options['orm']["cache"] = array(
            "metadata" => "array_cache"
            , "query"    => "apc_cache"
            , "result"   => "xcache_cache"
        );

        if (extension_loaded("apc") && extension_loaded("xcache")) {
            $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    self::$core, $this->options
            );
            $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag"
                , $doctrine->getCacheServices());

            foreach ($doctrine->getCacheServices()->all() as $key => $service) {
                if ($key === "metadata")
                    $this->assertEquals("array", $service->getType());
                elseif ($key === "query")
                    $this->assertEquals("apc", $service->getType());
                elseif ($key === "result")
                    $this->assertEquals("xcache", $service->getType());
            }
        } else {
            try {
                $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                        self::$core, $this->options
                );
                $this->fail("An exception should be raised");
            } catch (\Exception $e) {

            }
        }
    }

    public function testExceptionUnknowLogService()
    {
        try {
            $this->options["log"] = "unknowLogger";
            $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    self::$core, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testExceptionMissingDbal()
    {
        try {
            unset($this->options["dbal"]);
            $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    self::$core, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testExceptionUnknowDbal()
    {
        try {
            $this->options["dbal"] = "unknowDbal";
            $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    self::$core, $this->options
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }
}
