<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

class SessionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
        $this->XMLHTTPRequest('POST', '/session/update/', array(
            'usr' => self::$DI['user_alt1']->get_id()
        ));
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
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        $this->XMLHTTPRequest('POST', '/session/update/', array(
            'usr' => self::$DI['user']->get_id(),
            'module' => 1
        ));
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
        $auth = new \Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        $this->XMLHTTPRequest('POST', '/session/update/', array(
            'usr' => self::$DI['user']->get_id()
        ));

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

}
