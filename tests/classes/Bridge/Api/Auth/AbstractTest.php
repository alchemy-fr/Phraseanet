<?php

require_once __DIR__ . '/../../Bridge_datas.inc';

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class Bridge_Api_Auth_AbstractTest extends \PhraseanetTestCase
{

    public function testSet_settings()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Auth_Abstract');
        $setting = $this->createStub("Bridge_AccountSettings");
        $return = $stub->set_settings($setting);
        $this->assertEquals($stub, $return);
    }
}
