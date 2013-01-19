<?php

class API_OAuth2_ApplicationTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var API_OAuth2_Application
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');
    }

    public function tearDown()
    {
        $this->object->delete();
        parent::tearDown();
    }

    public function testLoad_from_client_id()
    {
        $client_id = $this->object->get_client_id();
        $loaded = API_OAuth2_Application::load_from_client_id(self::$DI['app'], $client_id);
        $this->assertInstanceOf('API_OAuth2_Application', $loaded);
        $this->assertEquals($this->object, $loaded);
    }

    public function testLoad_dev_app_by_user()
    {
        $apps = API_OAuth2_Application::load_dev_app_by_user(self::$DI['app'], self::$DI['user']);
        $this->assertTrue(is_array($apps));
        $this->assertTrue(count($apps) > 0);
        $found = false;
        foreach ($apps as $app) {
            if ($app->get_id() === $this->object->get_id())
                $found = true;
            $this->assertInstanceOf('API_OAuth2_Application', $app);
        }

        if ( ! $found)
            $this->fail();
    }

    public function testLoad_app_by_user()
    {
        $apps = API_OAuth2_Application::load_app_by_user(self::$DI['app'], self::$DI['user']);
        $this->assertTrue(is_array($apps));
        $this->assertTrue(count($apps) > 0);
        $found = false;

        foreach ($apps as $app) {
            if ($app->get_id() === $this->object->get_id())
                $found = true;
            $this->assertInstanceOf('API_OAuth2_Application', $app);
        }

        if ( ! $found)
            $this->fail();
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int($this->object->get_id()));
    }

    public function testGet_creator()
    {
        $this->assertInstanceOf('User_Adapter', $this->object->get_creator());
    }

    public function testGet_type()
    {
        $this->assertTrue(in_array($this->object->get_type(), array(API_OAuth2_Application::DESKTOP_TYPE, API_OAuth2_Application::WEB_TYPE)));
    }

    public function testGet_nonce()
    {
        $this->assertTrue(is_string($this->object->get_nonce()));
        $this->assertTrue(strlen($this->object->get_nonce()) === 6);
    }

    public function testSet_type()
    {
        try {
            $this->object->set_type('prout');
            $this->fail();
        } catch (Exception_InvalidArgument $e) {

        }

        $this->object->set_type(API_OAuth2_Application::WEB_TYPE);
        $this->assertEquals(API_OAuth2_Application::WEB_TYPE, $this->object->get_type());
        $this->object->set_type(API_OAuth2_Application::DESKTOP_TYPE);
        $this->assertEquals(API_OAuth2_Application::DESKTOP_TYPE, $this->object->get_type());
        $this->assertEquals(API_OAuth2_Application::NATIVE_APP_REDIRECT_URI, $this->object->get_redirect_uri());
    }

    public function testGet_name()
    {
        $this->assertEquals('test app', $this->object->get_name());
    }

    public function testSet_name()
    {
        $this->object->set_name('prout');
        $this->assertEquals('prout', $this->object->get_name());
    }

    public function testGet_description()
    {
        $this->assertEquals('', $this->object->get_description());
    }

    public function testSet_description()
    {
        $desc = 'prouti prouto prout prout';
        $this->object->set_description($desc);
        $this->assertEquals($desc, $this->object->get_description());
    }

    public function testGet_website()
    {
        $this->assertEquals('', $this->object->get_website());
    }

    public function testSet_website()
    {
        $site = 'http://www.example.com/';
        $this->object->set_website($site);
        $this->assertEquals($site, $this->object->get_website());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
    }

    public function testGet_last_modified()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_last_modified());
    }

    protected function assertmd5($md5)
    {
        $this->assertTrue((count(preg_match('/[a-z0-9]{32}/', $md5)) === 1));
    }

    public function testGet_client_id()
    {
        $this->assertMd5($this->object->get_client_id());
    }

    public function testSet_client_id()
    {
        $client_id = md5('prouto');
        $this->object->set_client_id($client_id);
        $this->assertEquals($client_id, $this->object->get_client_id());
        $this->assertMd5($this->object->get_client_id());
    }

    public function testGet_client_secret()
    {
        $this->assertMd5($this->object->get_client_secret());
    }

    public function testSet_client_secret()
    {
        $client_secret = md5('prouto');
        $this->object->set_client_secret($client_secret);
        $this->assertEquals($client_secret, $this->object->get_client_secret());
        $this->assertMd5($this->object->get_client_secret());
    }

    public function testGet_redirect_uri()
    {
        $this->assertEquals('', $this->object->get_redirect_uri());
    }

    public function testSet_redirect_uri()
    {
        $uri = 'http://www.example.com/callback/';
        $this->object->set_redirect_uri($uri);
        $this->assertEquals($uri, $this->object->get_redirect_uri());
    }

    public function testGet_user_account()
    {
        $this->assertInstanceOf('API_OAuth2_Account', $this->object->get_user_account(self::$DI['user']));
    }
}
