<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class API_OAuth2_AccountTest extends PhraseanetPHPUnitAbstract
{

    /**
     * @var API_OAuth2_Account
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->application = API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');
        $this->object = API_OAuth2_Account::load_with_user(self::$DI['app'], $this->application, self::$DI['user']);
    }

    public function tearDown()
    {
        $this->application->delete();
        parent::tearDown();
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int($this->object->get_id()));
    }

    public function testGet_user()
    {
        $this->assertInstanceOf('User_Adapter', $this->object->get_user());
        $this->assertEquals(self::$DI['user']->get_id(), $this->object->get_user()->get_id());
    }

    public function testGet_api_version()
    {
        $this->assertEquals('1.0', $this->object->get_api_version());
    }

    public function testIs_revoked()
    {
        $this->assertTrue(is_bool($this->object->is_revoked()));
        $this->assertFalse($this->object->is_revoked());
    }

    public function testSet_revoked()
    {
        $this->object->set_revoked(true);
        $this->assertTrue($this->object->is_revoked());
        $this->object->set_revoked(false);
        $this->assertFalse($this->object->is_revoked());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
    }

    public function testGet_token()
    {
        $this->assertInstanceOf('API_OAuth2_Token', $this->object->get_token());
    }

    public function testGet_application()
    {
        $this->assertInstanceOf('API_OAuth2_Application', $this->object->get_application());
        $this->assertEquals($this->application, $this->object->get_application());
    }

    public function testLoad_with_user()
    {
        $loaded = API_OAuth2_Account::load_with_user(self::$DI['app'], $this->application, self::$DI['user']);
        $this->assertInstanceOf('API_OAuth2_Account', $loaded);
        $this->assertEquals($this->object, $loaded);
    }
}
