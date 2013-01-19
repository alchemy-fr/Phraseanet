<?php

namespace Alchemy\Tests\Phrasea\Controller\User;

class NotificationsTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::listNotifications
     */
    public function testListNotifications()
    {
        $this->XMLHTTPRequest('GET', '/user/notifications/');
        $response = self::$DI['client']->getResponse();
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
        $this->XMLHTTPRequest('POST', '/user/notifications/read/', array(
            'notifications' => ''
        ));
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success'], $response->getContent());
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
