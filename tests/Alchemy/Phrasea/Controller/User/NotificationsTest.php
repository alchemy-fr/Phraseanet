<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\User\Notifications;
use Symfony\Component\HttpFoundation\Request;

class NotificationsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::listNotifications
     */
    public function testListNotifications()
    {
        $response = $this->XMLHTTPRequest('GET', '/user/notifications/');
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Notifications::listNotifications
     */
    public function testListNotificationsNoXMLHTTPRequests()
    {
        self::$DI['client']->request('GET', '/user/notifications/');
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Notifications::setNotificationsReaded
     */
    public function testSetNotificationsReadedNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/notifications/read/');
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::setNotificationsReaded
     */
    public function testSetNotificationsReaded()
    {
        $response = $this->XMLHTTPRequest('POST', '/user/notifications/read/', array(
            'notifications' => array()
            ));
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Notifications::connect
     * @covers Alchemy\Phrasea\Controller\User\Notifications::call
     */
    public function testRequireAuthentication()
    {
        self::$DI['app']['phraseanet.user'] = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_guest'))
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['phraseanet.user']->expects($this->once())
            ->method('is_guest')
            ->will($this->returnValue(true));

        self::$DI['client']->request('GET', '/user/notifications/');
    }
}
