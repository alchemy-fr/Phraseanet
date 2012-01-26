<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Core as PhraseaCore;
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ConfigurationTest extends \PhraseanetPHPUnitAbstract
{

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $confProd;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $confDev;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $confTest;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $confNotInstalled;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $confExperience;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration
   */
  protected $object;

  public function setUp()
  {
    parent::setUp();

    $specNotInstalled = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFile')
    );

    $specNotInstalled->expects($this->any())
            ->method('getConfigurationFile')
            ->will(
                    $this->throwException(new Exception)
    );

    $specExperience = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFile')
    );

    $specExperience->expects($this->any())
            ->method('getConfigurationFile')
            ->will(
                    $this->returnValue(
                            new \SplFileObject(__DIR__ . '/confTestFiles/config.yml')
                    )
    );

    $handler = new Configuration\Handler($specNotInstalled, new Configuration\Parser\Yaml());
    $this->confNotInstalled = new PhraseaCore\Configuration($handler);


    $handler = new Configuration\Handler($specExperience, new Configuration\Parser\Yaml());
    $this->object = new PhraseaCore\Configuration($handler);
  }

  public function testGetEnvironment()
  {
    $this->assertEquals("dev", $this->object->getEnvironnement());
    $this->assertEquals(null, $this->confNotInstalled->getEnvironnement());
  }

  public function testSetEnvironment()
  {
    $this->object->setEnvironnement("test");
    $this->assertEquals("test", $this->object->getEnvironnement());
    $this->confNotInstalled->setEnvironnement("prod");
    $this->assertEquals("prod", $this->confNotInstalled->getEnvironnement());

    try
    {
      $this->object->setEnvironnement("unknow");
      $this->fail("should raise exception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testIsDebug()
  {
    $this->object->setEnvironnement("test");
    $this->assertTrue($this->object->isDebug());
    $this->object->setEnvironnement("dev");
    $this->assertTrue($this->object->isDebug());
    $this->object->setEnvironnement("prod");
    $this->assertFalse($this->object->isDebug());
    $this->object->setEnvironnement("no_debug");
    $this->assertFalse($this->object->isDebug());
  }

  public function testIsMaintened()
  {
    $this->object->setEnvironnement("test");
    $this->assertFalse($this->object->isMaintained());
    $this->object->setEnvironnement("dev");
    $this->assertFalse($this->object->isMaintained());
    $this->object->setEnvironnement("prod");
    $this->assertFalse($this->object->isMaintained());
    $this->object->setEnvironnement("no_maintenance");
    $this->assertFalse($this->object->isMaintained());
  }

  public function testIsDisplayingErrors()
  {
    $this->object->setEnvironnement("test");
    $this->assertTrue($this->object->isDisplayingErrors());
    $this->object->setEnvironnement("dev");
    $this->assertTrue($this->object->isDisplayingErrors());
    $this->object->setEnvironnement("prod");
    $this->assertFalse($this->object->isDisplayingErrors());
    $this->object->setEnvironnement("no_display_errors");
    $this->assertFalse($this->object->isDisplayingErrors());
  }

  public function testGetPhraseanet()
  {
    $this->object->setEnvironnement("test");
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->object->getPhraseanet());
    $this->object->setEnvironnement("dev");
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->object->getPhraseanet());
    $this->object->setEnvironnement("prod");
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->object->getPhraseanet());
    $this->object->setEnvironnement("missing_phraseanet");
    try
    {
      $this->object->getPhraseanet();
      $this->fail("should raise an exeception");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testisInstalled()
  {
    $this->assertFalse($this->confNotInstalled->isInstalled());
    $this->assertTrue($this->object->isInstalled());
  }

  public function testGetConfiguration()
  {
    $config = $this->object->getConfiguration();
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $config);
    $this->assertNotEmpty($config->all());
    $config = $this->confNotInstalled->getConfiguration();
    $this->assertEmpty($config->all());
  }

  public function testGetConnexions()
  {
    $connexions = $this->object->getConnexions();
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $connexions);
    $this->assertGreaterThan(0, sizeof($connexions->all()));
  }

  public function testGetConnexion()
  {
    $connexion = $this->object->getConnexion();
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $connexion);
    $this->assertGreaterThan(0, sizeof($connexion->all()));
  }

  public function testGetConnexionException()
  {
    try
    {
      $this->object->getConnexion('unknow_connexion');
      $this->fail('should raise an exception');
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testGetFile()
  {
    $this->assertInstanceOf("\SplFileObject", $this->object->getFile());
  }

  public function testGetFileExeption()
  {
    try
    {
      $this->assertInstanceOf("\SplFileObject", $this->confNotInstalled->getFile());
      $this->fail("should raise an excpetion");
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testAll()
  {
    $all = $this->object->all();
    $this->assertTrue(is_array($all));
    $this->assertArrayHasKey("test", $all);
    $this->assertArrayHasKey("dev", $all);
    $this->assertArrayHasKey("prod", $all);
    $this->assertArrayHasKey("environment", $all);
  }

  public function testGetServices()
  {
    $services = $this->object->getServices();
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $services);
    $this->assertGreaterThan(0, sizeof($services->all()));
  }

  public function testGetService()
  {
    $services = $this->object->getService('twig');
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $services);
    $this->assertGreaterThan(0, sizeof($services->all()));
  }

  public function testGetServiceException()
  {
    try
    {
      $this->object->getService('unknow_service');
      $this->fail('should raise an exception');
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testWrite()
  {
    touch(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationPathName')
    );

    $file = new \SplFileObject(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub->expects($this->any())
            ->method('getConfigurationPathName')
            ->will(
                    $this->returnValue($file->getPathname())
    );

    $handler = new Configuration\Handler($stub, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);

    $arrayToBeWritten = array(
        'hello' => 'world'
        , 'key' => array(
            'keyone' => 'valueone'
            , 'keytwo' => 'valuetwo'
        )
    );

    $configuration->write($arrayToBeWritten, 0, true);

    $all = $configuration->all();

    $this->assertArrayHasKey("hello", $all);
    $this->assertArrayHasKey("key", $all);
    $this->assertTrue(is_array($all["key"]));
  }

  public function testWriteException()
  {
    touch(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationPathName')
    );

    $file = new \SplFileObject(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub->expects($this->any())
            ->method('getConfigurationPathName')
            ->will(
                    $this->returnValue("unknow_path")
    );

    $handler = new Configuration\Handler($stub, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);

    $arrayToBeWritten = array(
        'hello' => 'world'
        , 'key' => array(
            'keyone' => 'valueone'
            , 'keytwo' => 'valuetwo'
        )
    );

    try
    {
      $configuration->write($arrayToBeWritten);
      $this->fail("should raise an exception");
    }
    catch (\exception $e)
    {
      
    }
  }

  public function testDelete()
  {
    touch(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationPathName')
    );

    $file = new \SplFileObject(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub->expects($this->any())
            ->method('getConfigurationPathName')
            ->will(
                    $this->returnValue($file->getPathname())
    );

    $handler = new Configuration\Handler($stub, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);

    $configuration->delete();

    $this->assertFileNotExists($file->getPathname());
  }

  public function testDeleteException()
  {
    touch(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationPathName')
    );

    $file = new \SplFileObject(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub->expects($this->any())
            ->method('getConfigurationPathName')
            ->will(
                    $this->returnValue("unknow_path")
    );

    $handler = new Configuration\Handler($stub, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);

    try
    {
      $configuration->delete();
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }

    $this->assertFileExists($file->getPathname());

    unlink(__DIR__ . "/confTestFiles/yamlWriteTest.yml");
  }

  public function testGetTemplating()
  {
    try
    {
      $templating = $this->object->getTemplating();
    }
    catch (\Exception $e)
    {
      $this->fail("not template_engine provided");
    }
    $this->assertTrue(is_string($templating));
  }

  public function testGetOrm()
  {
    try
    {
      $orm = $this->object->getOrm();
    }
    catch (\Exception $e)
    {
      $this->fail("not template_engine provided");
    }
    $this->assertTrue(is_string($orm));
  }

  public function testGetServiceFile()
  {
    $this->assertInstanceOf("\SplFileObject", $this->object->getServiceFile());
  }

  public function testGetConnexionFile()
  {
    $this->assertInstanceOf("\SplFileObject", $this->object->getConnexionFile());
  }

  public function testRefresh()
  {
    $this->confNotInstalled->refresh();
    $this->assertFalse($this->confNotInstalled->isInstalled());
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->confNotInstalled->getConfiguration());

    touch(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationPathName')
    );

    $file = new \SplFileObject(__DIR__ . "/confTestFiles/yamlWriteTest.yml");

    $stub->expects($this->any())
            ->method('getConfigurationPathName')
            ->will(
                    $this->returnValue($file->getPathname())
    );

    $handler = new Configuration\Handler($stub, new Configuration\Parser\Yaml());

    $configuration = new PhraseaCore\Configuration($handler);

    $newScope = array("prod" => array('key' => 'value', 'key2' => 'value2'));

    //append new conf
    $configuration->write($newScope, FILE_APPEND);

    try
    {
      $configuration->getConfiguration();//it is not loaded
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {
      
    }

    $configuration->refresh(); //reload conf
    $prod = $configuration->getConfiguration();
    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $prod);
    
    unlink(__DIR__ . "/confTestFiles/yamlWriteTest.yml");
  }

  public function testSetHandler()
  {
    $handler = new Configuration\Handler(new Configuration\Application(), new Configuration\Parser\Yaml());
    $this->object->setConfigurationHandler($handler);
    $this->assertEquals($handler, $this->object->getConfigurationHandler());
  }

  public function testGetHandler()
  {
    $stub = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFile')
    );
    
    $handler = new Configuration\Handler($stub, new Configuration\Parser\Yaml());
    
    $this->assertEquals($handler, $this->object->getConfigurationHandler());
  }
}

