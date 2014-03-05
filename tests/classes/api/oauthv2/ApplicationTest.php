<?php
use Alchemy\Phrasea\Model\Entities\ApiApplication;

class api_oauthv2_ApplicationTest extends \PhraseanetTestCase
{
    public function testLoad_from_client_id()
    {
        $loaded = self::$DI['app']['repo.api-applications']->findByClientId(self::$DI['oauth2-app-user']->getClientId());
        $this->assertInstanceOf('ApiApplication', $loaded);
        $this->assertEquals(self::$DI['oauth2-app-user'], $loaded);
    }

    public function testLoad_dev_app_by_user()
    {
        $apps = self::$DI['app']['repo.api-applications']->findByCreator(self::$DI['user']);
        $this->assertTrue(is_array($apps));
        $this->assertTrue(count($apps) > 0);
        $found = false;
        foreach ($apps as $app) {
            if ($app->get_id() === self::$DI['oauth2-app-user']->getId()) {
                $found = true;
            }
            $this->assertInstanceOf('ApiApplication', $app);
        }

        if (!$found) {
            $this->fail();
        }
    }

    public function testLoad_app_by_user()
    {
        $apps = self::$DI['app']['repo.api-applications']->findByUser(self::$DI['user']);
        $this->assertTrue(is_array($apps));
        $this->assertTrue(count($apps) > 0);
        $found = false;

        foreach ($apps as $app) {
            if ($app->get_id() === self::$DI['oauth2-app-user']->get_id()) {
                $found = true;
            }
            $this->assertInstanceOf('ApiApplication', $app);
        }

        if (!$found) {
            $this->fail();
        }
    }

    public function testGettersAndSetters()
    {
        $this->assertTrue(is_int(self::$DI['oauth2-app-user']->getId()));
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', self::$DI['oauth2-app-user']->getCreator());
        $this->assertEquals(self::$DI['user']->getId(), self::$DI['oauth2-app-user']->getCreator()->getId());
        $this->assertTrue(in_array(self::$DI['oauth2-app-user']->getType(), [ApiApplication::DESKTOP_TYPE, ApiApplication::WEB_TYPE]));
        $this->assertTrue(is_string(self::$DI['oauth2-app-user']->getNonce()));
        $this->assertEquals(64, strlen(self::$DI['oauth2-app-user']->getNonce()));
        self::$DI['oauth2-app-user']->set_type(ApiApplication::WEB_TYPE);
        $this->assertEquals(ApiApplication::WEB_TYPE, self::$DI['oauth2-app-user']->getType());
        self::$DI['oauth2-app-user']->set_type(ApiApplication::DESKTOP_TYPE);
        $this->assertEquals(ApiApplication::DESKTOP_TYPE, self::$DI['oauth2-app-user']->getType());
        $this->assertEquals(ApiApplication::NATIVE_APP_REDIRECT_URI, self::$DI['oauth2-app-user']->getRedirectUri());
        self::$DI['oauth2-app-user']->setType(ApiApplication::WEB_TYPE);

        self::$DI['oauth2-app-user']->setName('prout');
        $this->assertEquals('prout', self::$DI['oauth2-app-user']->getName());
        self::$DI['oauth2-app-user']->setName('test application for user');
        $this->assertEquals('test application for user', self::$DI['oauth2-app-user']->getName());

        $desc = 'prouti prouto prout prout';
        self::$DI['oauth2-app-user']->setDescription($desc);
        $this->assertEquals($desc, self::$DI['oauth2-app-user']->getDescription());
        self::$DI['oauth2-app-user']->setDescription('');
        $this->assertEquals('', self::$DI['oauth2-app-user']->getDescription());

        $site = 'http://www.example.com/';
        self::$DI['oauth2-app-user']->setWebsite($site);
        $this->assertEquals($site, self::$DI['oauth2-app-user']->getWebsite());
        self::$DI['oauth2-app-user']->setWebsite('');
        $this->assertEquals('', self::$DI['oauth2-app-user']->getWebsite());

        $this->assertInstanceOf('DateTime', self::$DI['oauth2-app-user']->getCreated());
        $this->assertInstanceOf('DateTime', self::$DI['oauth2-app-user']->getUpdated());

        $this->assertMd5(self::$DI['oauth2-app-user']->getClientId());

        $client_id = md5('prouto');
        self::$DI['oauth2-app-user']->seClientId($client_id);
        $this->assertEquals($client_id, self::$DI['oauth2-app-user']->getClientId());
        $this->assertMd5(self::$DI['oauth2-app-user']->getClientId());

        $this->assertMd5(self::$DI['oauth2-app-user']->getClientSecret());

        $client_secret = md5('prouto');
        self::$DI['oauth2-app-user']->setClientSecret($client_secret);
        $this->assertEquals($client_secret, self::$DI['oauth2-app-user']->getClientSecret());
        $this->assertMd5(self::$DI['oauth2-app-user']->getClientSecret());

        $uri = 'http://www.example.com/callback/';
        self::$DI['oauth2-app-user']->setRedirectUri($uri);
        $this->assertEquals($uri, self::$DI['oauth2-app-user']->getRedirectUri());
    }

    private function assertmd5($md5)
    {
        $this->assertTrue((count(preg_match('/[a-z0-9]{32}/', $md5)) === 1));
    }
}
