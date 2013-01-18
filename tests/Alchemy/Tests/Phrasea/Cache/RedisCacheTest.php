<?php

namespace Alchemy\Tests\Phrasea\Cache;

use \Alchemy\Phrasea\Cache\RedisCache;

class RedisTest extends \PhraseanetPHPUnitAbstract
{

    public function testBasics()
    {
        if (extension_loaded('Redis')) {
            $redis = new \Redis();
            try {
                $ok = @$redis->connect('127.0.0.1', 6379);
            } catch (\Exception $e) {
                $ok = false;
            }
            if ( ! $ok) {
                $this->markTestSkipped('The ' . __CLASS__ . ' requires the use of redis');
            }
        } else {
            $this->markTestSkipped('The ' . __CLASS__ . ' requires the use of redis');
        }

        $cache = new RedisCache();
        $cache->setRedis($redis);
        // Test save
        $cache->save('test_key', 'testing this out');

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key'));

        $cache->save('test_key1', 'testing this out', 20);

        // Test contains to test that save() worked
        $this->assertTrue($cache->contains('test_key1'));

        // Test fetch
        $this->assertEquals('testing this out', $cache->fetch('test_key'));

        // Test delete
        $cache->save('test_key2', 'test2');
        $cache->delete('test_key2');
        $this->assertFalse($cache->contains('test_key2'));

        $this->assertEquals($redis, $cache->getRedis());
    }
}
