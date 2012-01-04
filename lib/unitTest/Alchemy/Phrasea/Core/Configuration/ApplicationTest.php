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

  public function testGetConfigurationFilePath()
  {
    $app = new Application();
    $this->assertTrue(is_string($app->getConfigurationFilePath()));
  }

  public function testGetConfigurationFile()
  {
    $app = new Application();
    
    try
    {
      $this->assertInstanceOf('SplFileObject', $app->getConfigurationFile());
    }
    catch(Exception $e)
    {
      $this->fail('Config file config.yml is not present');
    }
  }

  public function testGetConfFileExtension()
  {
    $app = new Application();
    $this->assertEquals('yml', $app->getConfigurationFileExtension());
  }

  public function testIsExtended()
  {
    $app = new Application();
    
    $envs = array(Application::KEYWORD_ENV => 'dev');
    $this->assertEquals('dev', $app->getSelectedEnv($envs));
    $envs = array('blabla' => 'blabla');
    $this->assertEquals(Application::DEFAULT_ENV, $app->getSelectedEnv($envs));
  }

}
