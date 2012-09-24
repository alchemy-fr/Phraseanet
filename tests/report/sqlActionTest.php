<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class sqlActionTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    protected $action;
    protected $mock;

    public function setUp()
    {
        parent::setUp();

        $this->mock = $this->getMock('module_report', array(), array(), '', false);

        $this->action = new module_report_sqlaction(self::$application, $this->mock);
    }

    public function testGetAction()
    {
        $this->assertEquals('add', $this->action->getAction());
        $this->action->setAction('unknowAction');
        $this->assertEquals('add', $this->action->getAction());
    }
}
