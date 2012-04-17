<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../../../PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class DoctrineMonologTest extends PhraseanetPHPUnitAbstract
{

 protected $options = array(
      "handler"  => "rotate"
      , "filename" => "test"
      , 'output'   => 'json'
      , 'channel'   => 'test'
    );

  public function setUp()
  {
    parent::setUp();
  }

  public function testService()
  {

    $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
        self::$core, $this->options
    );

    $this->assertInstanceOf("\Doctrine\Logger\MonologSQLLogger", $log->getDriver());
  }

  public function testType()
  {
    $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
        self::$core, $this->options
    );

    $this->assertEquals("doctrine_monolog", $log->getType());
  }

  public function testExceptionBadOutput()
  {
    try
    {
      $this->options["output"] = "unknowOutput";
      $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Monolog(
          self::$core, $this->options
      );
      $log->getDriver();
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

}
