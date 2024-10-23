<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Model\Entities\ApiApplication;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class DevelopersTest extends \PhraseanetAuthenticatedWebTestCase
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
        $form = $crawler->selectButton(self::$DI['app']['translator']->trans('boutton::valider'))->form();
        $this->assertEquals('/developers/application/', $form->getFormNode()->getAttribute('action'));
        $this->assertEquals('POST', $form->getMethod());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Developers::newApp
     */
    public function testPostNewAppInvalidArguments()
    {
        $randomValue = $this->setSessionFormToken('newApplication');

        $crawler = self::$DI['client']->request('POST', '/developers/application/', [
            'type'            => ApiApplication::WEB_TYPE,
            'name'            => '',
            'description'     => 'okok',
            'website'         => 'my.website.com',
            'callback'        => 'my.callback.com',
            'scheme-website'  => 'http://',
            'scheme-callback' => 'http://',
            'newApplication_token' => $randomValue
            ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $form = $crawler->selectButton(self::$DI['app']['translator']->trans('boutton::valider'))->form();
        $this->assertEquals('okok', $form['description']->getValue());
        $this->assertEquals('my.website.com', $form['website']->getValue());
        $this->assertEquals('my.callback.com', $form['callback']->getValue());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root\Developers::newApp
     */
    public function testPostNewApp()
    {
        $apps = self::$DI['app']['repo.api-applications']->findByCreator(self::$DI['user']);
        $nbApp = count($apps);
        $randomValue = $this->setSessionFormToken('newApplication');

        self::$DI['client']->request('POST', '/developers/application/', [
            'type'            => ApiApplication::WEB_TYPE,
            'name'            => 'hello',
            'description'     => 'okok',
            'website'         => 'my.website.com',
            'callback'        => 'my.callback.com',
            'scheme-website'  => 'http://',
            'scheme-callback' => 'http://',
            'newApplication_token' => $randomValue
        ]);

        $apps = self::$DI['app']['repo.api-applications']->findByCreator(self::$DI['user']);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertGreaterThan($nbApp, count($apps));
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::getApp
     */
    public function testGetUnknownApp()
    {
        self::$DI['client']->request('GET', '/developers/application/0/');

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::getApp
     */
    public function testGetApp()
    {
        $oauthApp = self::$DI['oauth2-app-user'];
        self::$DI['client']->request('GET', '/developers/application/' . $oauthApp->getId() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::deleteApp
     */
    public function testDeleteAppBadRequest()
    {
        self::$DI['client']->request('DELETE', '/developers/application/1/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::deleteApp
     */
    public function testDeleteAppError()
    {
        $response = $this->XMLHTTPRequest('DELETE', '/developers/application/0/');

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::deleteApp
     */
    public function testDeleteApp()
    {
        $oauthApp = self::$DI['app']['manipulator.api-application']->create(
            'test app',
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://phraseanet.com/'
        );
        $id = $oauthApp->getId();
        $response = $this->XMLHTTPRequest('DELETE', '/developers/application/' . $id . '/');
        $this->assertTrue($response->isOk());

        $this->assertNull(self::$DI['app']['repo.api-applications']->find($id));
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallbackBadRequest()
    {
        self::$DI['client']->request('POST', '/developers/application/1/callback/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallbackError()
    {
        $response = $this->XMLHTTPRequest('POST', '/developers/application/0/callback/', [
            'callback' => 'my.callback.com'
        ]);

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallbackError2()
    {
        $oauthApp = self::$DI['oauth2-app-user'];
        $response = $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->getId() . '/callback/');
        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallback()
    {
        $oauthApp = self::$DI['oauth2-app-user'];

        $response = $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->getId() . '/callback/', [
            'callback' => 'http://my.callback.com'
        ]);

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertTrue($content->success);
        $oauthApp = self::$DI['app']['repo.api-applications']->find($oauthApp->getId());
        $this->assertEquals('http://my.callback.com', $oauthApp->getRedirectUri());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAccessToken
     */
    public function testRenewAccessTokenbadRequest()
    {
        self::$DI['client']->request('POST', '/developers/application/1/access_token/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAccessToken
     */
    public function testRenewAccessTokenError()
    {
        $response = $this->XMLHTTPRequest('POST', '/developers/application/0/access_token/', [
            'callback' => 'my.callback.com'
        ]);

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAccessToken
     */
    public function testRenewAccessToken()
    {
        $oauthApp = self::$DI['oauth2-app-user'];

        $response = $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->getId() . '/access_token/');

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertTrue($content->success);
        $this->assertNotNull($content->token);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     */
    public function testAuthorizeGrantpasswordBadRequest()
    {
        self::$DI['client']->request('POST', '/developers/application/1/authorize_grant_password/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     */
    public function testAuthorizeGrantpasswordError()
    {
        $response = $this->XMLHTTPRequest('POST', '/developers/application/0/authorize_grant_password/', [
            'callback' => 'my.callback.com'
        ]);

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     */
    public function testAuthorizeGrantpasswordToken()
    {
        $oauthApp = self::$DI['oauth2-app-user'];

        $response = $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->getId() . '/authorize_grant_password/', [
            'grant' => '1'
        ]);

        $this->assertTrue($response->isOk());
        $content = json_decode($response->getContent());
        $this->assertTrue($content->success);
        $oauthApp = self::$DI['app']['repo.api-applications']->find($oauthApp->getId());
        $this->assertTrue($oauthApp->isPasswordGranted());
    }
}
