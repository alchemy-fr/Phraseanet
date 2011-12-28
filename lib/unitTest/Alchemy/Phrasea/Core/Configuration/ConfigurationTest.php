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
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ConfigurationTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  public function setUp()
  {
    parent::setUp();
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  public function testInitialization()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getMainConfigurationFile')
    );

    $fileName = __DIR__ . '/confTestFiles/good.yml';

    $spec->expects($this->any())
            ->method('getMainConfigurationFile')
            ->will(
                    $this->returnValue(
                            new \SplFileObject($fileName)
                    )
    );

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $environnement = 'main';
    $configuration = new PhraseaCore\Configuration($environnement, $handler);

    $this->assertEquals($environnement, $configuration->getEnvironnement());
    $this->assertTrue($configuration->isInstalled());
    $this->assertInstanceOf(
            'Alchemy\Phrasea\Core\Configuration\Parameter'
            , $configuration->getConfiguration()
    );
    $this->assertFalse($configuration->isDebug());
    $this->assertFalse($configuration->displayErrors());
    $this->assertTrue(is_array($configuration->getPhraseanet()));
    $this->assertTrue(is_array($configuration->getDoctrine()));
  }

  public function testInstalled()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getMainConfigurationFile')
    );

    $spec->expects($this->any())
            ->method('getMainConfigurationFile')
            ->will($this->throwException(new \Exception()));

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $environnement = 'main';
    $configuration = new PhraseaCore\Configuration($environnement, $handler);

    $this->assertFalse($configuration->isInstalled());
    $this->assertTrue(is_array($configuration->getPhraseanet()));
    $this->assertTrue(is_array($configuration->getDoctrine()));
  }

  public function testGetAvailableLogger()
  {
    $spec = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $environnement = 'main';
    $configuration = new PhraseaCore\Configuration($environnement, $handler);
    
    $availableLogger = $configuration->getAvailableDoctrineLogger();
    
    $this->assertTrue(is_array($availableLogger));
    $this->assertContains('monolog', $availableLogger);
    $this->assertContains('echo', $availableLogger);
  }
  
  public function testGetHandler()
  {
    $spec = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $environnement = 'main';
    $configuration = new PhraseaCore\Configuration($environnement, $handler);
    
    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Configuration\Handler', $configuration->getConfigurationHandler());
  }
  
  public function testSetHandler()
  {
    $spec = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $environnement = 'main';
    $configuration = new PhraseaCore\Configuration($environnement, $handler);
    
    $spec2 = $this->getMock('\Alchemy\Phrasea\Core\Configuration\Application');
    
    $spec2->expects($this->any())
            ->method('getMainConfigurationFile')
            ->will(
                    $this->returnValue(
                            'test'
                    )
    );
    
    $newHandler = new Configuration\Handler($spec2, new Configuration\Parser\Yaml());
    
    $configuration->setConfigurationHandler($newHandler);
    
    $this->assertEquals('test', $configuration->getConfigurationHandler()->getSpecification()->getMainConfigurationFile());
  }

  public function testBadDoctrineLogger()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getMainConfigurationFile')
    );

    $fileName = __DIR__ . '/confTestFiles/bad_doctrine_logger.yml';

    $spec->expects($this->any())
            ->method('getMainConfigurationFile')
            ->will(
                    $this->returnValue(
                            new \SplFileObject($fileName)
                    )
    );

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $environnement = 'main';
    $configuration = new PhraseaCore\Configuration($environnement, $handler);
    
    try
    {
      $configuration->getDoctrine();
      $this->fail('An exception should be raised');
    }
    catch(Exception $e)
    {
      
    }
  }
}