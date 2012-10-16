<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Prod\Share;

class ShareTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     * @covers Alchemy\Phrasea\Controller\Prod\Share::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Share::call
     */
    public function testMountedRouteSlash()
    {
        $url = sprintf('/prod/share/record/%d/%d/', self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
        self::$DI['client']->request('GET', $url);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     * @covers Alchemy\Phrasea\Controller\Prod\Share::connect
     */
    public function testRouteSlash()
    {
        self::$DI['app']['phraseanet.user'] = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('ACL'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();

        //has_right_on_base return true
        $stubbedACL->expects($this->once())
            ->method('has_right_on_sbas')
            ->will($this->returnValue(true));

        //has_access_to_subdef return true
        $stubbedACL->expects($this->once())
            ->method('has_access_to_subdef')
            ->will($this->returnValue(true));

        self::$DI['app']['phraseanet.user']->expects($this->any())
            ->method('ACL')
            ->will($this->returnValue($stubbedACL));

        $url = sprintf('/prod/share/record/%d/%d/', self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
        self::$DI['client']->request('GET', $url);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     */
    public function testShareRecord()
    {
        $share = new Share();
        $response = $share->shareRecord(self::$DI['app'], $this->getMock('Symfony\Component\HttpFoundation\Request'), self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
        $this->assertTrue($response->isOk());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\Prod\Share::shareRecord
     */
    public function testShareRecordBadAccess()
    {
        $share = new Share();

        self::$DI['app']['phraseanet.user'] = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('ACL'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();

        //has_access_to_subdef return false
        $stubbedACL->expects($this->once())
            ->method('has_access_to_subdef')
            ->will($this->returnValue(false));

        self::$DI['app']['phraseanet.user']->expects($this->once())
            ->method('ACL')
            ->will($this->returnValue($stubbedACL));

        $share->shareRecord(self::$DI['app'], $this->getMock('Symfony\Component\HttpFoundation\Request'), self::$DI['record_1']->get_base_id(), self::$DI['record_1']->get_record_id());
    }
}
