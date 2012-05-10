<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class CacheAutoloaderTest extends \PhraseanetPHPUnitAbstract
{
    private $apc = false;
    private $xcache = false;

    public function setUp()
    {
        parent::setUp();

        if (extension_loaded('apc')) {
            $this->apc = true;
        }

        if (extension_loaded('xcache')) {
            $this->xcache = true;
        }
    }

    public function testConstruct()
    {
        if ( ! $this->apc && ! $this->xcache) {
            try {
                $autoloader = new Alchemy\Phrasea\Loader\CacheAutoloader('test_prefix_');
                $this->fail("should raise an exception");
            } catch (\Exception $e) {

            }
        }
    }

    public function testFindFileApc()
    {
        if ($this->apc) {
            if ( ! (ini_get('apc.enabled') && ini_get('apc.enable_cli'))) {
                $this->markTestSkipped('The apc extension is available, but not enabled.');
            } else {
                apc_clear_cache('user');
            }

            $autoloader = new Alchemy\Phrasea\Loader\CacheAutoloader('test_prefix_');
            $cacheAdapter = $autoloader->getAdapter();
            $this->assertEquals($autoloader->findFile('Test_HelloCache'), $cacheAdapter->fetch('test_prefix_Test_Hello'));
        }
    }

    public function testGetPrefix()
    {
        if ($this->apc) {
            $autoloader = new Alchemy\Phrasea\Loader\CacheAutoloader('test_prefix_');
            $this->assertEquals('test_prefix_', $autoloader->getPrefix());
        }
    }

    public function testRegister()
    {
        if ($this->apc) {
            if ( ! (ini_get('apc.enabled') && ini_get('apc.enable_cli'))) {
                $this->markTestSkipped('The apc extension is available, but not enabled.');
            } else {
                apc_clear_cache('user');
            }
            $autoloader = new Alchemy\Phrasea\Loader\CacheAutoloader('test_prefix_');
            $autoloader->addPath('fixture', __DIR__ . '/Fixtures');
            $autoloader->register();
            $this->assertTrue(class_exists("Test_test"));
        }
    }

    public function testFindFileXcache()
    {
        if ($this->xcache) {
            $this->marktestSkipped("can't use xcache in cli mode");
        }
    }

    public function tearDown()
    {
        if (ini_get('apc.enabled') && ini_get('apc.enable_cli')) {
            apc_clear_cache('user');
        }
        parent::tearDown();
    }
}
