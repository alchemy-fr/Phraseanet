<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class MemcacheCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MemcacheCache
     */
    protected $object;

    public function setUp()
    {
        $this->object = new \Alchemy\Phrasea\Cache\MemcacheCache;

        if ( ! class_exists('Memcache')) {
            $this->markTestSkipped('No memcache extension');
        }

        $memcache = new Memcache();
        if ( ! @$memcache->connect('localhost', 11211)) {
            $this->markTestSkipped('No memcache server');
        }

        $this->object->setMemcache($memcache);
    }

    public function testIsServer()
    {
        $this->assertTrue(is_bool($this->object->isServer()));
    }

    public function testGetStats()
    {
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

