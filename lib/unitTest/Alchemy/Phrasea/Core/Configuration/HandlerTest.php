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

use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Core\Configuration\Application;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class handlerTest extends \PhraseanetPHPUnitAbstract
{

  public function testGetSpec()
  {
    $spec = $this->getMock(
            '\Alchemy\Phrasea\Core\Configuration\Application'
            , array('getConfigurationFile')
    );

    $fileName = __DIR__ . '/confTestFiles/config.yml';

    $spec->expects($this->any())
            ->method('getConfigurationFile')
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
            , array('getConfigurationFile')
    );

    $fileName = __DIR__ . '/confTestFiles/config.yml';

    $spec->expects($this->any())
            ->method('getConfigurationFile')
            ->will(
                    $this->returnValue(
                            new \SplFileObject($fileName)
                    )
    );

    $handler = new Configuration\Handler($spec, new Configuration\Parser\Yaml());

    $this->assertInstanceOf('\Alchemy\Phrasea\Core\Configuration\Parser', $handler->getParser());
  }

  public function testHandle()
  {
    try
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
    catch (\Exception $e)
    {
      $this->fail($e->getMessage());
    }
  }

  public function testHandleDataNotfound()
  {
    try
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
    catch (\Exception $e)
    {
      $this->fail($e->getMessage());
    }
  }

  public function testHandleException()
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

    try
    {
      $result = $handler->handle('unknowEnv');
      $this->fail($e->getMessage());
    }
    catch (\Exception $e)
    {

    }
  }

}
