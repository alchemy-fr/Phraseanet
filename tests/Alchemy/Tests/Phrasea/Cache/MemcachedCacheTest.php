<?php

namespace Alchemy\Tests\Phrasea\Cache;

use Alchemy\Phrasea\Cache\MemcachedCache;

class MemcachedCacheTest extends \PhraseanetTestCase
{
    /**
     * @var MemcacheCache
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new MemcachedCache();

        if (!class_exists('Memcached')) {
            $this->markTestSkipped('No memcached extension');
        }

        $memcached = new \Memcached();
        if (!@$memcached->addServer('localhost', 11211)) {
            $this->markTestSkipped('No memcached server');
        }

        $this->object->setMemcached($memcached);
    }

    public function testIsServer()
    {
        $this->assertTrue($this->object->isServer());
    }

    public function testGetStats()
    {
        $this->assertTrue(is_array($this->object->getStats()));
    }

    public function testDeleteMulti()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
