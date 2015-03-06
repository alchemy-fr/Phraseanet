<?php

class report_abstractReportTestCase extends \PhraseanetTestCase
{
    public function setUp()
    {
        parent::setUp();

        $mock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')->disableOriginalConstructor()->setMethods(array('getUser'))->getMock();

        $mock->expects($this->any())->method('getUser')->will($this->returnValue(self::$DI['user']));

        self::$DI['app']['authentication'] = $mock;
    }
}
