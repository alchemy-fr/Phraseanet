<?php

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class randomTest extends PhraseanetPHPUnitAbstract
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
        $usr_id = self::$DI['user']->get_id();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, $expires_on, 'some nice datas');
        $this->random->cleanTokens(self::$DI['app']);

        try {
            $this->random->helloToken($token);
            $this->fail();
        } catch (NotFoundHttpException $e) {

        }
    }

    public function testGeneratePassword()
    {
        $this->assertRegExp('/[a-zA-Z]{4}/', random::generatePassword(4, random::LETTERS));
        $this->assertRegExp('/[a-zA-Z]{8}/', random::generatePassword(8, random::LETTERS));
        $this->assertRegExp('/[a-zA-Z]{16}/', random::generatePassword(16, random::LETTERS));
        $this->assertRegExp('/[a-zA-Z]{32}/', random::generatePassword(32, random::LETTERS));
        $this->assertRegExp('/[a-zA-Z]{64}/', random::generatePassword(64, random::LETTERS));
        $this->assertRegExp('/[a-zA-Z0-9]{4}/', random::generatePassword(4, random::LETTERS_AND_NUMBERS));
        $this->assertRegExp('/[a-zA-Z0-9]{8}/', random::generatePassword(8, random::LETTERS_AND_NUMBERS));
        $this->assertRegExp('/[a-zA-Z0-9]{16}/', random::generatePassword(16, random::LETTERS_AND_NUMBERS));
        $this->assertRegExp('/[a-zA-Z0-9]{32}/', random::generatePassword(32, random::LETTERS_AND_NUMBERS));
        $this->assertRegExp('/[a-zA-Z0-9]{64}/', random::generatePassword(64, random::LETTERS_AND_NUMBERS));
        $this->assertRegExp('/[0-9]{4}/', random::generatePassword(4, random::NUMBERS));
        $this->assertRegExp('/[0-9]{8}/', random::generatePassword(8, random::NUMBERS));
        $this->assertRegExp('/[0-9]{16}/', random::generatePassword(16, random::NUMBERS));
        $this->assertRegExp('/[0-9]{32}/', random::generatePassword(32, random::NUMBERS));
        $this->assertRegExp('/[0-9]{64}/', random::generatePassword(64, random::NUMBERS));
        try {
            random::generatePassword('gros caca', random::NUMBERS);
            $this->fail('An invalid argument exception should have been triggered');
        } catch (Exception_InvalidArgument $e) {

        }
        try {
            random::generatePassword('012', random::NUMBERS);
            $this->fail('An invalid argument exception should have been triggered');
        } catch (Exception_InvalidArgument $e) {

        }
        try {
            random::generatePassword('caca007', random::NUMBERS);
            $this->fail('An invalid argument exception should have been triggered');
        } catch (Exception_InvalidArgument $e) {

        }
    }

    public function testGetUrlToken()
    {
        $usr_id = self::$DI['user']->get_id();
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
        $usr_id = self::$DI['user']->get_id();
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
        $usr_id = self::$DI['user']->get_id();
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
        $usr_id = self::$DI['user']->get_id();
        $token = $this->random->getUrlToken(\random::TYPE_PASSWORD, $usr_id, $expires_on, 'some nice datas');

        try {
            $this->random->helloToken($token);
            $this->fail();
        } catch (NotFoundHttpException $e) {

        }
    }
}
