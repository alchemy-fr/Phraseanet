<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class RedisTest extends \PhraseanetPHPUnitAbstract
{

  public function testBasics()
  {
    if (extension_loaded('memcached'))
    {
      $redis = new Redis();
      $ok = @$redis->connect('127.0.0.1', 6379);
      if (!$ok)
      {
        $this->markTestSkipped('The ' . __CLASS__ . ' requires the use of redis');
      }
    }
    else
    {
      $this->markTestSkipped('The ' . __CLASS__ . ' requires the use of redis');
    }

    $cache = new \Alchemy\Phrasea\Cache\RedisCache();
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

    $ids = $cache->getIds();
    $this->assertTrue(in_array('test_key', $ids));
    
    $this->assertEquals($redis, $cache->getRedis());
  }

}