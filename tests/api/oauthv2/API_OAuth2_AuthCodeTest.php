<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class API_OAuth2_AuthCodeTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var API_OAuth2_AuthCode
     */
    protected $object;
    protected $code;

    protected $account;

    public function setUp()
    {
        parent::setUp();
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $this->application = API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');
        $this->account = API_OAuth2_Account::load_with_user(self::$DI['app'], $this->application, self::$DI['user']);

        $expires = time() + 100;
        $this->code = random::generatePassword(8);

        $this->object = API_OAuth2_AuthCode::create(self::$DI['app'], $this->account, $this->code, $expires);
    }

    public function tearDown()
    {
        $this->application->delete();
        parent::tearDown();
    }

    public function testGet_code()
    {
        $this->assertEquals($this->code, $this->object->get_code());
    }

    public function testGet_account()
    {
        $this->assertInstanceOf('API_OAuth2_Account', $this->object->get_account());
    }

    public function testGet_redirect_uri()
    {
        $this->assertEquals('', $this->object->get_redirect_uri());
    }

    public function testSet_redirect_uri()
    {
        $redirect_uri = 'https://www.google.com';
        $this->assertEquals('', $this->object->get_redirect_uri());
        $this->object->set_redirect_uri($redirect_uri);
        $this->assertEquals($redirect_uri, $this->object->get_redirect_uri());
    }

    public function testGet_expires()
    {
        $this->assertInternalType('string', $this->object->get_expires());
    }

    public function testGet_scope()
    {
        $this->assertEquals('', $this->object->get_scope());
    }

    public function testSet_scope()
    {
        $scope = 'prout';
        $this->assertEquals('', $this->object->get_scope());
        $this->object->set_scope($scope);
        $this->assertEquals($scope, $this->object->get_scope());
    }

    public function testLoad_codes_by_account()
    {
        $this->assertTrue(is_array(API_OAuth2_AuthCode::load_codes_by_account(self::$DI['app'], $this->account)));
        $this->assertTrue(count(API_OAuth2_AuthCode::load_codes_by_account(self::$DI['app'], $this->account)) > 0);
    }
}
