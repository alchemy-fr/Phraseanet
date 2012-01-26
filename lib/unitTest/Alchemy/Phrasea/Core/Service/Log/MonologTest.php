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
class MonologTest extends PhraseanetPHPUnitAbstract
{

  protected $options = array(
      "handler" => "rotate"
      , "filename" => "test"
  );

  public function setUp()
  {
    parent::setUp();
    $this->options = array(
        "handler" => "rotate"
        , "filename" => "test"
    );
  }

  public function testScope()
  {

    $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    'hello', $this->options, array()
    );

    $this->assertEquals("log", $log->getScope());
  }

  public function testService()
  {

    $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    'hello', $this->options, array()
    );

    $this->assertInstanceOf("\Monolog\Logger", $log->getService());
  }

  public function testType()
  {
    $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                    'hello', $this->options, array()
    );

    $this->assertEquals("monolog", $log->getType());
  }

  public function testExceptionMissingOptions()
  {
    try
    {
      $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                      'hello', array(), array()
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testExceptionMissingHandler()
  {
    try
    {
      unset($this->options["handler"]);
      $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                      'hello', $this->options, array()
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testExceptionUnknowHandler()
  {
    try
    {
      $this->options["handler"] = "unknowHandler";
      $log = new \Alchemy\Phrasea\Core\Service\Log\Monolog(
                      'hello', $this->options, array()
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

}
