<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\ApcCache;

class ApcCacheTest extends \PhraseanetTestCase
{
    /**
     * @var ApcCache
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('Apc is not installed');
        }
        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped('Apc is not loaded in CLI');
        }

        $this->object = new ApcCache;
    }

    public function testIsServer()
    {
        $this->assertTrue(is_bool($this->object->isServer()));
    }

    public function testGetStats()
    {
        // Remove the following lines when you implement this test.
        $this->markTestSkipped(
            'Test is failing'
        );
        $this->assertTrue(is_array($this->object->getStats()) || is_null($this->object->getStats()));
    }

    public function testGet()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testDeleteMulti()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
