<?php

namespace Alchemy\Tests\Phrasea\Controller\User;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class NotificationsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testListNotifications()
    {
//        $response = $this->XMLHTTPRequest('GET', '/user/notifications/');
//        $this->assertTrue($response->isOk());

        $this->markTestSkipped();
    }

    public function testListNotificationsNoXMLHTTPRequests()
    {
//        self::$DI['client']->request('GET', '/user/notifications/');
//
//        $this->assertBadResponse(self::$DI['client']->getResponse());

        $this->markTestSkipped();
    }

    public function testSetNotificationsReadedNoXMLHTTPRequests()
    {
//        self::$DI['client']->request('POST', '/user/notifications/read/');
//
//        $this->assertBadResponse(self::$DI['client']->getResponse());

        $this->markTestSkipped();
    }

    public function testSetNotificationsReaded()
    {
//        $response = $this->XMLHTTPRequest('POST', '/user/notifications/read/', [
//            'notifications' => ''
//        ]);
//        $this->assertTrue($response->isOk());
//        $datas = (array) json_decode($response->getContent());
//        $this->assertArrayHasKey('success', $datas);
//        $this->assertTrue($datas['success'], $response->getContent());
//        $this->assertArrayHasKey('message', $datas);

        $this->markTestSkipped();
    }

    public function testRequireAuthentication()
    {
        self::$DI['app']['authentication']->setUser($this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')->disableOriginalConstructor()->onlyMethods(['isGuest'])->getMock());

        self::$DI['app']->getAuthenticatedUser()->expects($this->once())
            ->method('isGuest')
            ->will($this->returnValue(true));

        self::$DI['client']->request('GET', '/user/notifications/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }
}
