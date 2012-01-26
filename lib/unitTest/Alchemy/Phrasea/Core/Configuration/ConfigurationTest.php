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
    $this->object->setEnvironnement("ok");
    $this->assertEquals("ok", $this->object->getEnvironnement());
    $this->confNotInstalled->setEnvironnement("ok");
    $this->assertEquals("ok", $this->confNotInstalled->getEnvironnement());
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

//  public function testgetPhraseanet()
//  {
//    $this->object->setEnvironnement("test");
//    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->object->getPhraseanet());
//    $this->object->setEnvironnement("dev");
//    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->object->getPhraseanet());
//    $this->object->setEnvironnement("prod");
//    $this->assertInstanceOf("\Symfony\Component\DependencyInjection\ParameterBag\ParameterBag", $this->object->getPhraseanet());
//    $this->object->setEnvironnement("missing_phraseanet");
//    try
//    {
//      $this->object->getPhraseanet();
//      $this->fail("should raise an exeception");
//    }
//    catch (\Exception $e)
//    {
//
//    }
//  }

}
