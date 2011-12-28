<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Core\Configuration\Application;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class handlerTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  public function setUp()
  {
    parent::setUp();
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  public function testGetSpec()
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

    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Configuration\Specification', $handler->getSpecification());
  }

  public function testGetParser()
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

    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Configuration\Parser', $handler->getParser());
  }

  public function testRetrieveExtendedEnvFromFile()
  {

    $handler = new Configuration\Handler(new Application(), new Configuration\Parser\Yaml());

    $fileName = __DIR__ . '/confTestFiles/config_test.yml';
    $file = new \SplFileObject($fileName);

    $envs = $handler->retrieveExtendedEnvFromFile($file);

    $this->assertEquals(3, count($envs));
  }

  public function testRetrieveExtendedEnvFromFileNonExisting()
  {

    $handler = new Configuration\Handler(new Application(), new Configuration\Parser\Yaml());

    $fileName = __DIR__ . '/confTestFiles/extends_non_existing_file.yml';
    $file = new \SplFileObject($fileName);

    try
    {
      $envs = $handler->retrieveExtendedEnvFromFile($file);
      $this->fail('An exception should have been raised');
    }
    catch (Exception $e)
    {
      
    }
  }

  public function testHandle()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFilePath')
    );

    $spec->expects($this->any())
            ->method('getConfigurationFilePath')
            ->will(
                    $this->returnValue(
                            __DIR__ . '/confTestFiles'
                    )
    );

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $result = $handler->handle('test');

    $this->assertTrue(is_array($result));
  }

  public function testHandleDataNotfound()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFilePath', 'getNonExtendablePath')
    );

    $spec->expects($this->any())
            ->method('getConfigurationFilePath')
            ->will(
                    $this->returnValue(
                            __DIR__ . '/confTestFiles'
                    )
    );

    $spec->expects($this->any())
            ->method('getNonExtendablePath')
            ->will(
                    $this->returnValue(
                            array(array('NON', 'EXISTING', 'VALUE'))
                    )
    );

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $result = $handler->handle('test');

    $this->assertTrue(is_array($result));
  }

}