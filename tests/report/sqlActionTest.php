<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';


class sqlActionTest extends PhraseanetPHPUnitAbstract
{

  protected $action;

  public function setUp()
  {
    $this->action =  new module_report_sqlaction($this->getMock('module_report', array(), array() , '', false));

  }

  public function testGetAction()
  {
    $this->assertEquals('add', $this->action->getAction());
    $this->action->setAction('unknowAction');
    $this->assertEquals('add', $this->action->getAction());
  }
}
