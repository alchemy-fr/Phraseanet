<?php

class report_sqlActionTest extends \PhraseanetAuthenticatedTestCase
{
    protected $action;
    protected $mock;

    public function setUp()
    {
        parent::setUp();

        $this->mock = $this->getMock('module_report', [], [], '', false);

        $this->action = new module_report_sqlaction(self::$DI['app'], $this->mock);
    }

    public function testGetAction()
    {
        $this->assertEquals('add', $this->action->getAction());
        $this->action->setAction('unknowAction');
        $this->assertEquals('add', $this->action->getAction());
    }
}
