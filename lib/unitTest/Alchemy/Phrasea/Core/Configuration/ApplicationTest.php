<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Core as PhraseaCore;
use Alchemy\Phrasea\Core\Configuration\Application;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ApplicationTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration\Application 
   */
  public function setUp()
  {
    parent::setUp();
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  public function testGetNonExtendablePath()
  {
    $app = new Application();
    $paths = $app->getNonExtendablePath();
    $this->assertTrue(is_array($paths));
    foreach ($paths as $path)
    {
      $this->assertTrue(is_array($path));
      foreach ($path as $key)
      {
        $this->assertTrue(is_string($key));
      }
    }
  }

  public function testGetConfFileFromEnvName()
  {
    $app = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getMainConfigurationFile', 'getConfigurationFilePath')
    );

    $fileName = __DIR__ . '/confTestFiles/good.yml';

    $app->expects($this->any())
            ->method('getMainConfigurationFile')
            ->will(
                    $this->returnValue(
                            new \SplFileObject($fileName)
                    )
    );
    $app->expects($this->any())
            ->method('getConfigurationFilePath')
            ->will(
                    $this->returnValue(
                            __DIR__ . '/confTestFiles'
                    )
    );

    $this->assertInstanceOf('SplFileObject', $app->getConfFileFromEnvName('oneenv'));
    $this->assertInstanceOf('SplFileObject', $app->getConfFileFromEnvName(Application::EXTENDED_MAIN_KEYWORD));

    try
    {
      $app->getConfFileFromEnvName('unknow_env');
      $this->fail('An exception shoud be raised');
    }
    catch (\Exception $e)
    {
      
    }
  }

  public function testGetConfigurationFilePath()
  {
    $app = new Application();
    $this->assertTrue(is_string($app->getConfigurationFilePath()));
  }

  public function testGetMainConfigurationFile()
  {
    $app = new Application();
    try
    {
      $this->assertInstanceOf('SplFileObject', $app->getMainConfigurationFile());
    }
    catch(Exception $e)
    {
      $this->markTestSkipped('Config file config.yml is not present');
    }
  }

  public function testGetConfFileExtension()
  {
    $app = new Application();
    $this->assertEquals('yml', $app->getConfFileExtension());
  }

  public function testIsExtended()
  {
    $app = new Application();
    
    $testExtendedEnv = array('extends' => 'value');
    $testNonExtendedEnv = array('blabla' => 'blabla');
    
    $this->assertTrue($app->isExtended($testExtendedEnv));
    $this->assertFalse($app->isExtended($testNonExtendedEnv));
  }

  public function testGetExtendedEnvName()
  {
    $app = new Application();
    $testExtendedEnv = array('extends' => 'value');
    $testNonExtendedEnv = array('blabla' => 'blabla');
    
    $this->assertEquals('value', $app->getExtendedEnvName($testExtendedEnv));
    $this->assertNull($app->getExtendedEnvName($testNonExtendedEnv));
  }

}
