<?php

class API_OAuth2_ApplicationTest extends \PhraseanetTestCase
{
    public function testLoad_from_client_id()
    {
        $client_id = self::$DI['oauth2-app-user']->get_client_id();
        $loaded = API_OAuth2_Application::load_from_client_id(self::$DI['app'], $client_id);
        $this->assertInstanceOf('API_OAuth2_Application', $loaded);
        $this->assertEquals(self::$DI['oauth2-app-user'], $loaded);
    }

    public function testLoad_dev_app_by_user()
    {
        $apps = API_OAuth2_Application::load_dev_app_by_user(self::$DI['app'], self::$DI['user']);
        $this->assertTrue(is_array($apps));
        $this->assertTrue(count($apps) > 0);
        $found = false;
        foreach ($apps as $app) {
            if ($app->get_id() === self::$DI['oauth2-app-user']->get_id())
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
            if ($app->get_id() === self::$DI['oauth2-app-user']->get_id())
                $found = true;
            $this->assertInstanceOf('API_OAuth2_Application', $app);
        }

        if ( ! $found)
            $this->fail();
    }

    public function testGettersAndSetters()
    {
        $this->assertTrue(is_int(self::$DI['oauth2-app-user']->get_id()));
        $this->assertInstanceOf('User_Adapter', self::$DI['oauth2-app-user']->get_creator());
        $this->assertEquals(self::$DI['user']->get_id(), self::$DI['oauth2-app-user']->get_creator()->get_id());

        $this->assertTrue(in_array(self::$DI['oauth2-app-user']->get_type(), [API_OAuth2_Application::DESKTOP_TYPE, API_OAuth2_Application::WEB_TYPE]));

        $this->assertTrue(is_string(self::$DI['oauth2-app-user']->get_nonce()));
        $this->assertTrue(strlen(self::$DI['oauth2-app-user']->get_nonce()) === 6);

        try {
            self::$DI['oauth2-app-user']->set_type('prout');
            $this->fail();
        } catch (Exception_InvalidArgument $e) {

        }

        self::$DI['oauth2-app-user']->set_type(API_OAuth2_Application::WEB_TYPE);
        $this->assertEquals(API_OAuth2_Application::WEB_TYPE, self::$DI['oauth2-app-user']->get_type());
        self::$DI['oauth2-app-user']->set_type(API_OAuth2_Application::DESKTOP_TYPE);
        $this->assertEquals(API_OAuth2_Application::DESKTOP_TYPE, self::$DI['oauth2-app-user']->get_type());
        $this->assertEquals(API_OAuth2_Application::NATIVE_APP_REDIRECT_URI, self::$DI['oauth2-app-user']->get_redirect_uri());
        self::$DI['oauth2-app-user']->set_type(API_OAuth2_Application::WEB_TYPE);


        self::$DI['oauth2-app-user']->set_name('prout');
        $this->assertEquals('prout', self::$DI['oauth2-app-user']->get_name());
        self::$DI['oauth2-app-user']->set_name('test application for user');
        $this->assertEquals('test application for user', self::$DI['oauth2-app-user']->get_name());


        $desc = 'prouti prouto prout prout';
        self::$DI['oauth2-app-user']->set_description($desc);
        $this->assertEquals($desc, self::$DI['oauth2-app-user']->get_description());
        self::$DI['oauth2-app-user']->set_description('');
        $this->assertEquals('', self::$DI['oauth2-app-user']->get_description());


        $site = 'http://www.example.com/';
        self::$DI['oauth2-app-user']->set_website($site);
        $this->assertEquals($site, self::$DI['oauth2-app-user']->get_website());
        self::$DI['oauth2-app-user']->set_website('');
        $this->assertEquals('', self::$DI['oauth2-app-user']->get_website());

        $this->assertInstanceOf('DateTime', self::$DI['oauth2-app-user']->get_created_on());

        $this->assertInstanceOf('DateTime', self::$DI['oauth2-app-user']->get_last_modified());

        $this->assertMd5(self::$DI['oauth2-app-user']->get_client_id());

        $client_id = md5('prouto');
        self::$DI['oauth2-app-user']->set_client_id($client_id);
        $this->assertEquals($client_id, self::$DI['oauth2-app-user']->get_client_id());
        $this->assertMd5(self::$DI['oauth2-app-user']->get_client_id());

        $this->assertMd5(self::$DI['oauth2-app-user']->get_client_secret());

        $client_secret = md5('prouto');
        self::$DI['oauth2-app-user']->set_client_secret($client_secret);
        $this->assertEquals($client_secret, self::$DI['oauth2-app-user']->get_client_secret());
        $this->assertMd5(self::$DI['oauth2-app-user']->get_client_secret());

        $uri = 'http://www.example.com/callback/';
        self::$DI['oauth2-app-user']->set_redirect_uri($uri);
        $this->assertEquals($uri, self::$DI['oauth2-app-user']->get_redirect_uri());

        $this->assertInstanceOf('API_OAuth2_Account', self::$DI['oauth2-app-user']->get_user_account(self::$DI['user']));
    }

    private function assertmd5($md5)
    {
        $this->assertTrue((count(preg_match('/[a-z0-9]{32}/', $md5)) === 1));
    }
}
