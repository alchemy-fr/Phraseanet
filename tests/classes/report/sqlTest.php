<?php

class sqlTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

    public function setUp()
    {
        parent::setUp();
        $report = $this->getMock('module_report', array(), array(), '', false);
        $this->sql = new module_report_sql(self::$DI['app'], $report);
    }

    public function testSql()
    {
        $sqlFilter = $this->getMock('module_report_sqlfilter', array('getCorFilter'), array(), '', false);
        $sqlFilter->expects($this->any())->method('getCorFilter')->will($this->onConsecutiveCalls(array(), array('hello' => 'world')));
        $this->sql->setFilter($sqlFilter);
        $this->assertEquals('hello', $this->sql->getTransQuery('hello'));
        $this->assertEquals('world', $this->sql->getTransQuery('hello'));
        $this->sql->setGroupby('test');
        $this->assertEquals('test', $this->sql->getGroupBy());
        $this->sql->setOn('on');
        $this->assertEquals('on', $this->sql->getOn());
    }
}
