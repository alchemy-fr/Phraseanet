<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class report_sqlTest extends \report_abstractReportTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $report = $this->createMock('module_report');
        $report
            ->method('getSbasId')
            ->will($this->returnValue(self::$DI['collection']->get_databox()->get_sbas_id()));
        $this->sql = new module_report_sql(self::$DI['app'], $report);
    }

    public function testSql()
    {
        $sqlFilter = $this->createMock('module_report_sqlfilter');
        $sqlFilter->method('getCorFilter')->will($this->onConsecutiveCalls([], ['hello' => 'world']));
        $this->sql->setFilter($sqlFilter);
        $this->assertEquals('hello', $this->sql->getTransQuery('hello'));
        $this->assertEquals('world', $this->sql->getTransQuery('hello'));
        $this->sql->setGroupby('test');
        $this->assertEquals('test', $this->sql->getGroupBy());
        $this->sql->setOn('on');
        $this->assertEquals('on', $this->sql->getOn());
    }
}
