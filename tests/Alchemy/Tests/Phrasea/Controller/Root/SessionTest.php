<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Symfony\Component\HttpKernel\Client;

class SessionTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Session::updateSession
     */
    public function testUpdSessionLogout()
    {
        $this->logout(self::$DI['app']);
        $this->XMLHTTPRequest('POST', '/session/update/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->checkSessionReturn($datas);
        $this->assertEquals('disconnected', $datas->status);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Session::updateSession
     */
    public function testUpdSessionChangeUser()
    {
        $this->XMLHTTPRequest('POST', '/session/update/', [
            'usr' => self::$DI['user_alt1']->getId()
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->checkSessionReturn($datas);
        $this->assertEquals('disconnected', $datas->status);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Session::updateSession
     */
    public function testUpdSession()
    {
        $this->authenticate(self::$DI['app']);

        $this->XMLHTTPRequest('POST', '/session/update/', [
            'usr' => self::$DI['user']->getId(),
            'module' => 1
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->checkSessionReturn($datas);
        $this->assertEquals('ok', $datas->status);
    }

     /**
     * @covers \Alchemy\Phrasea\Controller\Root\Session::updateSession
     */
    public function testUpdSessionBadRequestMissingModuleArgument()
    {
        $this->authenticate(self::$DI['app']);

        $this->XMLHTTPRequest('POST', '/session/update/', [
            'usr' => self::$DI['user']->getId()
        ]);

        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $datas = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->checkSessionReturn($datas);
        $this->assertEquals('unknown', $datas->status);
    }

     /**
     * @covers \Alchemy\Phrasea\Controller\Root\Session::updateSession
     */
    public function testUpdSessionBadRequest()
    {
        self::$DI['client']->request('POST', '/session/update/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    private function checkSessionReturn(\stdClass $data)
    {
        $this->assertObjectHasAttribute('status', $data);
        $this->assertObjectHasAttribute('message', $data);
        $this->assertObjectHasAttribute('notifications', $data);
        $this->assertObjectHasAttribute('changed', $data);
    }

    public function testDeleteSession()
    {
        $originalEm = self::$DI['app']['EM'];

        $session = $this->getMock('Alchemy\Phrasea\Model\Entities\Session');

        $session->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(self::$DI['app']['authentication']->getUser()));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue($session));
        $em->expects($this->once())
            ->method('remove')
            ->will($this->returnValue(null));
        $em->expects($this->once())
            ->method('flush')
            ->will($this->returnValue(null));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);
        $this->XMLHTTPRequest('POST', '/session/delete/1');
        $this->assertTrue(self::$DI['client']->getResponse()->isOK());
        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $em = null;
    }

    public function testDeleteSessionUnauthorized()
    {
        $originalEm = self::$DI['app']['EM'];

        $session = $this->getMock('Alchemy\Phrasea\Model\Entities\Session');

        $session->expects($this->once())
            ->method('getUsrId')
            ->will($this->returnValue(self::$DI['app']['authentication']->getUser()->getId() + 1));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('find')
            ->will($this->returnValue($session));

        self::$DI['app']['EM'] = $em;
        self::$DI['client'] = new Client(self::$DI['app'], []);
        self::$DI['client']->request('POST', '/session/delete/1');
        $this->assertFalse(self::$DI['client']->getResponse()->isOK());
        $this->assertEquals(self::$DI['client']->getResponse()->getStatusCode(), 403);
        self::$DI['app']['EM'] = $originalEm;
        self::$DI['client'] = new Client(self::$DI['app'], []);

        $em = null;
    }
}
