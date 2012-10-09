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
        $notifications = new Notifications();
        $request = Request::create('/user/notifications/', 'GET', array(), array(), array() ,array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $notifications->listNotifications(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        unset($notifications, $request, $response);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Notifications::listNotifications
     */
    public function testListNotificationsNoXMLHTTPRequests()
    {
        $notifications = new Notifications();
        $request = Request::create('/user/notifications/', 'GET');
        $notifications->listNotifications(self::$DI['app'], $request);
        unset($notifications, $request);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @covers Alchemy\Phrasea\Controller\User\Notifications::setNotificationsReaded
     */
    public function testSetNotificationsReadedNoXMLHTTPRequests()
    {
        $notifications = new Notifications();
        $request = Request::create('/user/notifications/read/', 'POST');
        $notifications->listNotifications(self::$DI['app'], $request);
        unset($notifications, $request);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::setNotificationsReaded
     */
    public function testSetNotificationsReaded()
    {
        $notifications = new Notifications();
        $request = Request::create('/user/notifications/read/', 'POST', array(
            'notifications' => array()
        ), array(), array() ,array('HTTP_X-Requested-With' => 'XMLHttpRequest'));
        $response = $notifications->setNotificationsReaded(self::$DI['app'], $request);
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        $this->assertArrayHasKey('message', $datas);
        unset($notifications, $request, $response);
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

        self::$DI['app']['phraseanet.user'] ->expects($this->once())
            ->method('is_guest')
            ->will($this->returnValue(true));

        self::$DI['client']->request('GET', '/user/notifications/');
    }
}
