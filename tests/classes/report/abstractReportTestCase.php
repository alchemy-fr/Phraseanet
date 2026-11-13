<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class report_abstractReportTestCase extends \PhraseanetTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $mock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')->disableOriginalConstructor()->onlyMethods(array('getUser'))->getMock();

        $mock->method('getUser')->will($this->returnValue(self::$DI['user']));

        self::$DI['app']['authentication'] = $mock;
    }
}
