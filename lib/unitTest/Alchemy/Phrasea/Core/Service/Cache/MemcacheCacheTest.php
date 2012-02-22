<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ServiceMemcacheCacheTest extends PhraseanetPHPUnitAbstract
{

  public function testScope()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                    self::$core, 'hello', array()
    );

    $this->assertEquals("cache", $cache->getScope());
  }

  public function testService()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                    self::$core, 'hello', array()
    );

    if (extension_loaded('memcache'))
    {
      $service = $cache->getDriver();
      $this->assertTrue($service instanceof \Doctrine\Common\Cache\AbstractCache);
    }
    else
    {
      try
      {
        $cache->getDriver();
        $this->fail("should raise an exception");
      }
      catch (\Exception $e)
      {

      }
    }
  }

  public function testServiceException()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                    self::$core, 'hello', array()
    );

    try
    {
      $cache->getDriver();
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testType()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                    self::$core, 'hello', array()
    );

    $this->assertEquals("memcache", $cache->getType());
  }

  public function testHost()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                    self::$core, 'hello', array()
    );

    $this->assertEquals(\Alchemy\Phrasea\Core\Service\Cache\MemcacheCache::DEFAULT_HOST, $cache->getHost());
  }

  public function testPort()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\MemcacheCache(
                    self::$core, 'hello', array()
    );

    $this->assertEquals(\Alchemy\Phrasea\Core\Service\Cache\MemcacheCache::DEFAULT_PORT, $cache->getPort());
  }

}
