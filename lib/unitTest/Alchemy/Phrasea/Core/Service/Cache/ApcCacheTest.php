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
class ApcCacheTest extends PhraseanetPHPUnitAbstract
{

  public function testScope()
  {
    $registry = $this->getMock('RegistryInterface');

    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
                    'hello', array(), array('registry' => $registry)
    );

    $this->assertEquals("cache", $cache->getScope());
  }

  public function testService()
  {
    $registry = $this->getMock('RegistryInterface');

    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
                    'hello', array(), array('registry' => $registry)
    );

    if (extension_loaded('apc'))
    {
      $this->assertInstanceOf("\Doctrine\Common\Cache\AbstractCache", $cache->getService());
    }
    else
    {
      try
      {
        $cache->getService();
        $this->fail("should raise an exception");
      }
      catch (\Exception $e)
      {
        
      }
    }
  }

  public function testServiceException()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
                    'hello', array(), array('registry' => null)
    );

    try
    {
      $cache->getService();
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testType()
  {
    $cache = new \Alchemy\Phrasea\Core\Service\Cache\ApcCache(
                    'hello', array(), array('registry' => null)
    );

    $this->assertEquals("apc", $cache->getType());
  }

}