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

require_once dirname(__FILE__) . '/../PhraseanetPHPUnitAbstract.class.inc';


class sqlTest extends PhraseanetPHPUnitAbstract
{

  public function setUp()
  {
    $report = $this->getMock('module_report', array(), array(), '', false);
    $this->sql = new module_report_sql($report);
  }

  public function testSql()
  {
    $sqlFilter = $this->getMock('module_report_sqlfilter', array('getCorFilter'), array(), '', false);
    $sqlFilter->expects($this->any())->method('getCorFilter')->will($this->onConsecutiveCalls(array(), array('hello'=>'world')));
    $this->sql->setFilter($sqlFilter);
    $this->assertEquals('hello', $this->sql->getTransQuery('hello'));
    $this->assertEquals('world', $this->sql->getTransQuery('hello'));
    $this->sql->setGroupby('test');
    $this->assertEquals('test', $this->sql->getGroupBy());
    $this->sql->setOn('on');
    $this->assertEquals('on', $this->sql->getOn());
  }
}
