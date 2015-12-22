<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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
        $this->authenticate($this->getApplication());

        /** @var User $user */
        $user = self::$DI['user'];
        $this->XMLHTTPRequest('POST', '/session/update/', [
            'usr' => $user->getId(),
            'module' => 1
        ]);
        $client = $this->getClient();
        $this->assertTrue($client->getResponse()->isOk());
        $data = json_decode($client->getResponse()->getContent());
        $this->checkSessionReturn($data);
        $this->assertEquals('ok', $data->status);
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
        $session = $this->getMock('Alchemy\Phrasea\Model\Entities\Session');

        $session->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(self::$DI['app']->getAuthenticatedUser()));

        $em = $this->createEntityManagerMock();

        self::$DI['app']['repo.sessions'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.sessions']->expects($this->exactly(2))
            ->method('find')
            ->will($this->returnValue($session));

        $em->expects($this->once())
            ->method('remove')
            ->will($this->returnValue(null));
        $em->expects($this->once())
            ->method('flush')
            ->will($this->returnValue(null));

        self::$DI['app']['orm.em'] = $em;
        $this->XMLHTTPRequest('POST', '/session/delete/1');
        $this->assertTrue(self::$DI['client']->getResponse()->isOK());
    }

    public function testDeleteSessionUnauthorized()
    {
        $session = $this->getMock('Alchemy\Phrasea\Model\Entities\Session');

        $session->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(self::$DI['user_alt1']));

        $em = $this->createEntityManagerMock();

        self::$DI['app']['repo.sessions'] = $this->createEntityRepositoryMock();
        self::$DI['app']['repo.sessions']->expects($this->exactly(2))
            ->method('find')
            ->will($this->returnValue($session));

        self::$DI['app']['orm.em'] = $em;
        self::$DI['client']->request('POST', '/session/delete/1');
        $this->assertFalse(self::$DI['client']->getResponse()->isOK());
        $this->assertEquals(self::$DI['client']->getResponse()->getStatusCode(), 403);
    }
}
