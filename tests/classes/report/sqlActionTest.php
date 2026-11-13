<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class report_sqlActionTest extends \report_abstractReportTestCase
{
    protected $action;
    protected $mock;

    public function setUp(): void
    {
        parent::setUp();

        $this->mock = $this->createMock('module_report');
        $this->mock
            ->method('getSbasId')
            ->will($this->returnValue(self::$DI['collection']->get_databox()->get_sbas_id()));

        $this->action = new module_report_sqlaction(self::$DI['app'], $this->mock);
    }

    public function testGetAction()
    {
        $this->assertEquals('add', $this->action->getAction());
        $this->action->setAction('unknowAction');
        $this->assertEquals('add', $this->action->getAction());
    }
}
