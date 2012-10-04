<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class DevelopersTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Developers::listApps
     */
    public function testListApps()
    {
        self::$DI['client']->request('GET', '/developers/applications/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Developers::displayFormApp
     */
    public function testDisplayformApp()
    {
        $crawler = self::$DI['client']->request('GET', '/developers/application/new/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $form = $crawler->selectButton(_('boutton::valider'))->form();
        $this->assertEquals('/developers/application/', $form->getFormNode()->getAttribute('action'));
        $this->assertEquals('POST', $form->getMethod());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Developers::newApp
     */
    public function testPostNewAppInvalidArguments()
    {
        $crawler = self::$DI['client']->request('POST', '/developers/application/', array(
            'type'            => \API_OAuth2_Application::WEB_TYPE,
            'name'            => '',
            'description'     => 'okok',
            'website'         => 'my.website.com',
            'callback'        => 'my.callback.com',
            'scheme-website'  => 'http://',
            'scheme-callback' => 'http://'
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $form = $crawler->selectButton(_('boutton::valider'))->form();
        $this->assertEquals('okok', $form['description']->getValue());
        $this->assertEquals('my.website.com', $form['website']->getValue());
        $this->assertEquals('my.callback.com', $form['callback']->getValue());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Developers::newApp
     */
    public function testPostNewApp()
    {
        $apps = API_OAuth2_Application::load_dev_app_by_user(self::$DI['app'], self::$DI['user']);
        $nbApp = count($apps);

        self::$DI['client']->request('POST', '/developers/application/', array(
            'type'            => \API_OAuth2_Application::WEB_TYPE,
            'name'            => 'hello',
            'description'     => 'okok',
            'website'         => 'my.website.com',
            'callback'        => 'my.callback.com',
            'scheme-website'  => 'http://',
            'scheme-callback' => 'http://'
        ));

        $apps = API_OAuth2_Application::load_dev_app_by_user(self::$DI['app'], self::$DI['user']);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertGreaterThan($nbApp, count($apps));
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::getApp
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testGetUnknowApp()
    {
        self::$DI['client']->request('GET', '/developers/application/0/');
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::getApp
     */
    public function testGetApp()
    {
        $oauthApp = \API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');
        self::$DI['client']->request('GET', '/developers/application/' . $oauthApp->get_id() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $oauthApp->delete();
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::deleteApp
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testDeleteAppBadRequest()
    {
        self::$DI['client']->request('DELETE', '/developers/application/1/');
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::deleteApp
     */
    public function testDeleteAppError()
    {
        $this->XMLHTTPRequest('DELETE', '/developers/application/0/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::deleteApp
     */
    public function testDeleteApp()
    {
        $oauthApp = \API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');

        $this->XMLHTTPRequest('DELETE', '/developers/application/' . $oauthApp->get_id() . '/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        try {
            new \API_OAuth2_Application(self::$DI['app'], $oauthApp->get_id());
            $this->fail('Application not deleted');
        } catch (\Exception_NotFound $e) {

        }
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testRenewAppCallbackBadRequest()
    {
        self::$DI['client']->request('POST', '/developers/application/1/callback/');
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallbackError()
    {
        $this->XMLHTTPRequest('POST', '/developers/application/0/callback/', array(
            'callback' => 'my.callback.com'
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallbackError2()
    {
         $oauthApp = \API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');

        $this->XMLHTTPRequest('POST', '/developers/application/'.$oauthApp->get_id().'/callback/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallback()
    {
        $oauthApp = \API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');

        $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->get_id() . '/callback/', array(
            'callback' => 'my.callback.com'
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue($content->success);
        $oauthApp = new \API_OAuth2_Application(self::$DI['app'], $oauthApp->get_id());
        $this->assertEquals('my.callback.com', $oauthApp->get_redirect_uri());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAccessToken
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testRenewAccessTokenbadRequest()
    {
        self::$DI['client']->request('POST', '/developers/application/1/access_token/');
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAccessToken
     */
    public function testRenewAccessTokenError()
    {
        $this->XMLHTTPRequest('POST', '/developers/application/0/access_token/', array(
            'callback' => 'my.callback.com'
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
        $this->assertNull($content->token);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAccessToken
     */
    public function testRenewAccessToken()
    {
        $oauthApp = \API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');

        $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->get_id() . '/access_token/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue($content->success);
        $this->assertNotNull($content->token);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testAuthorizeGrantpasswordBadRequest()
    {
        self::$DI['client']->request('POST', '/developers/application/1/authorize_grant_password/');
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     */
    public function testAuthorizeGrantpasswordError()
    {
        $this->XMLHTTPRequest('POST', '/developers/application/0/authorize_grant_password/', array(
            'callback' => 'my.callback.com'
            ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     */
    public function testAuthorizeGrantpasswordToken()
    {
        $oauthApp = \API_OAuth2_Application::create(self::$DI['app'], self::$DI['user'], 'test app');

        $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->get_id() . '/authorize_grant_password/', array(
            'grant' => '1'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue($content->success);
        $oauthApp = new \API_OAuth2_Application(self::$DI['app'], $oauthApp->get_id());
        $this->assertTrue($oauthApp->is_password_granted());
    }
}
