<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class randomTest extends \PhraseanetTestCase
{
    protected $random;

    public function setUp()
    {
        parent::setUp();
        $this->random = new \random(self::$DI['app']);
    }

    public function testCleanTokens()
    {
        $expires_on = new DateTime('-5 minutes');
        $usr_id = self::$DI['user']->getId();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, $expires_on, 'some nice datas');
        $this->random->cleanTokens(self::$DI['app']);

        try {
            $this->random->helloToken($token);
            $this->fail();
        } catch (NotFoundHttpException $e) {

        }
    }

    public function testGetUrlToken()
    {
        $usr_id = self::$DI['user']->getId();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, null, 'some nice datas');
        $datas = $this->random->helloToken($token);
        $this->assertEquals('some nice datas', $datas['datas']);
        $this->random->updateToken($token, 'some very nice datas');
        $datas = $this->random->helloToken($token);
        $this->assertEquals('some very nice datas', $datas['datas']);
        $this->random->removeToken($token);
    }

    public function testRemoveToken()
    {
        $this->testGetUrlToken();
    }

    public function testUpdateToken()
    {
        $this->testGetUrlToken();
    }

    public function testHelloToken()
    {
        $usr_id = self::$DI['user']->getId();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, null, 'some nice datas');
        $datas = $this->random->helloToken($token);
        $this->assertEquals('some nice datas', $datas['datas']);
        $this->assertNull($datas['expire_on']);
        $created_on = new DateTime($datas['created_on']);
        $date = new DateTime('-3 seconds');
        $this->assertTrue($date < $created_on, "asserting that " . $date->format(DATE_ATOM) . " is before " . $created_on->format(DATE_ATOM));
        $date = new DateTime();
        $this->assertTrue($date >= $created_on);
        $this->assertEquals('password', $datas['type']);

        $this->random->removeToken($token);
        try {
            $this->random->helloToken($token);
            $this->fail();
        } catch (NotFoundHttpException $e) {

        }

        $expires_on = new DateTime('+5 minutes');
        $usr_id = self::$DI['user']->getId();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, $expires_on, 'some nice datas');
        $datas = $this->random->helloToken($token);
        $this->assertEquals('some nice datas', $datas['datas']);
        $sql_expires = new DateTime($datas['expire_on']);
        $this->assertTrue($sql_expires == $expires_on);
        $created_on = new DateTime($datas['created_on']);
        $date = new DateTime('-3 seconds');
        $this->assertTrue($date < $created_on);
        $date = new DateTime();
        $this->assertTrue($date >= $created_on);
        $this->assertEquals('password', $datas['type']);

        $this->random->removeToken($token);
        try {
            $this->random->helloToken($token);
            $this->fail();
        } catch (NotFoundHttpException $e) {

        }

        $expires_on = new DateTime('-5 minutes');
        $usr_id = self::$DI['user']->getId();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, $expires_on, 'some nice datas');

        try {
            $this->random->helloToken($token);
            $this->fail();
        } catch (NotFoundHttpException $e) {

        }
    }
}
