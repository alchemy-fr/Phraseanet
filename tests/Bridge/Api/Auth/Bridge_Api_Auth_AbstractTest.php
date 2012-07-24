<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../Bridge_datas.inc';

class Bridge_Api_Auth_AbstractTest extends PHPUnit_Framework_TestCase
{

    public function testSet_settings()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Auth_Abstract');
        $setting = $this->getMock("Bridge_AccountSettings", array(), array(), '', false);
        $return = $stub->set_settings($setting);
        $this->assertEquals($stub, $return);
    }
}
