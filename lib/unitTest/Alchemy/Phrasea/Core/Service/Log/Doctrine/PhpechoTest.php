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
class DoctrinePhpechoTest extends PhraseanetPHPUnitAbstract
{

  public function testService()
  {

    $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Phpecho(
                    'hello', array(), array()
    );

    $this->assertInstanceOf("\Doctrine\DBAL\Logging\EchoSQLLogger", $log->getService());
  }

  public function testType()
  {
    $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Phpecho(
                    'hello', array(), array()
    );

    $this->assertEquals("phpecho", $log->getType());
  }

  public function testScope()
  {
    $log = new \Alchemy\Phrasea\Core\Service\Log\Doctrine\Phpecho(
                    'hello', array(), array());
    $this->assertEquals("log", $log->getScope());
  }

}
