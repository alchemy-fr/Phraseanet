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
class DoctrineTest extends PhraseanetPHPUnitAbstract
{

  protected $options;

  public function setUp()
  {
    parent::setUp();
    $this->options = array(
        "debug" => false
        , "log" => "sql_logger"
        , "dbal" => "main_connexion"
        , "orm" => array(
            "cache" => array(
                "metadata" => "array_cache"
                , "query" => "array_cache"
                , "result" => "array_cache"
            )
        )
    );
  }

  public function testScope()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    $this->assertEquals("orm", $doctrine->getScope());
  }

  public function testService()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    $this->assertInstanceOf("\Doctrine\ORM\EntityManager", $doctrine->getService());
  }

  public function testType()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    $this->assertEquals("doctrine", $doctrine->getType());
  }

  public function testExceptionMissingOptions()
  {
    try
    {
      $registry = $this->getMock('RegistryInterface');
      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                      'hello', array(), array('registry' => $registry)
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testNoCacheInOptions()
  {
    $registry = $this->getMock('RegistryInterface');
    unset($this->options["orm"]["cache"]);
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    foreach ($doctrine->getCacheServices()->all() as $service)
    {
      $this->assertEquals("array", $service->getType());
    }
  }

  public function testUnknowCache()
  {
    $registry = $this->getMock('RegistryInterface');
    $this->options["orm"]["cache"]["result"] = "unknowCache";
    
    try
    {
      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                      'hello', $this->options, array('registry' => $registry)
      );
      $this->fail("An exception should be raised");
    }
    catch(\Exception $e)
    {
      
    }
  }

  public function testIsDebug()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    $this->assertFalse($doctrine->isDebug());

    $this->options['debug'] = true;
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    $this->assertTrue($doctrine->isDebug());
  }

  public function testGetCacheServices()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag"
            , $doctrine->getCacheServices());

    foreach ($doctrine->getCacheServices()->all() as $service)
    {
      $this->assertEquals("array", $service->getType());
    }

    $this->options['orm']["cache"] = array(
        "metadata" => "array_cache"
        , "query" => "apc_cache"
        , "result" => "xcache_cache"
    );

    if (extension_loaded("apc") && extension_loaded("xcache"))
    {
      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                      'hello', $this->options, array('registry' => $registry)
      );
      $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag"
              , $doctrine->getCacheServices());

      foreach ($doctrine->getCacheServices()->all() as $key => $service)
      {
        if ($key === "metadata")
          $this->assertEquals("array", $service->getType());
        elseif ($key === "query")
          $this->assertEquals("apc", $service->getType());
        elseif ($key === "result")
          $this->assertEquals("xcache", $service->getType());
      }
    }
    else
    {
      try
      {
        $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                        'hello', $this->options, array('registry' => $registry)
        );
        $this->fail("An exception should be raised");
      }
      catch (\Exception $e)
      {
        
      }
    }
  }

  public function testExceptionUnknowLogService()
  {
    try
    {
      $registry = $this->getMock('RegistryInterface');
      $this->options["log"] = "unknowLogger";
      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                      'hello', $this->options, array('registry' => $registry)
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testExceptionMissingDbal()
  {
    try
    {
      $registry = $this->getMock('RegistryInterface');
      unset($this->options["dbal"]);
      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                      'hello', $this->options, array('registry' => $registry)
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testExceptionUnknowDbal()
  {
    try
    {
      $registry = $this->getMock('RegistryInterface');
      $this->options["dbal"] = "unknowDbal";
      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                      'hello', $this->options, array('registry' => $registry)
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testGetVersion()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
                    'hello', $this->options, array('registry' => $registry)
    );

    $this->assertEquals(\Doctrine\Common\Version::VERSION, $doctrine->getVersion());
  }

//  public function testBadDbal()
//  {
//    if (!extension_loaded('test_helpers'))
//    {
//      $this->fail("test_helpers extension required");
//    }
//
//
//    $handler = new \Alchemy\Phrasea\Core\Configuration\Handler(
//                    new \Alchemy\Phrasea\Core\Configuration\Application()
//                    , new \Alchemy\Phrasea\Core\Configuration\Parser\Yaml()
//    );
//    $class = $this->getMock(
//            '\Alchemy\Phrasea\Core\Configuration'
//            , array('getConnexion')
//            , array($handler)
//            , 'ConfMock'
//    );
//
//    $empty = new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag();
//
//    $class->expects($this->once())
//            ->method('getConnexion')
//            ->at()
//            ->with($this->equalTo("main_connexion"))
//            ->will($this->returnCallback("callback"));
//
//    try
//    {
//      $registry = $this->getMock('RegistryInterface');
//      set_new_overload(array($this, 'newCallback'));
//      $doctrine = new \Alchemy\Phrasea\Core\Service\Orm\Doctrine(
//                      'hello', $this->options, array('registry' => $registry)
//      );
//      unset_new_overload();
//      $this->fail("should raise an exception");
//    }
//    catch (\Exception $e)
//    {
//      
//    }
//  }
//
//  protected function newCallback($className)
//  {
//    switch ($className)
//    {
//      case 'Alchemy\Phrasea\Core\Configuration': 
//        echo('yo');return 'ConfMock';
//        break;
//      default: 
//        return $className;
//        break;
//    }
//  }
}
