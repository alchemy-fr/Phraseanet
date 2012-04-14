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
class ServiceArrayCacheTest extends PhraseanetPHPUnitAbstract
{

  public function testService()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
        self::$core, array()
    );

    $service = $cache->getDriver();
    $this->assertTrue($service instanceof \Doctrine\Common\Cache\CacheProvider);
  }

  public function testServiceException()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
        self::$core, array()
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
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ArrayCache(
        self::$core, array()
    );

    $this->assertEquals("array", $cache->getType());
  }

}
