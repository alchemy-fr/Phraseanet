<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $crawler = self::$DI['client']->request('POST', '/developers/application/', [
            'type'            => ApiApplication::WEB_TYPE,
            'name'            => '',
            'description'     => 'okok',
            'website'         => 'my.website.com',
            'callback'        => 'my.callback.com',
            'scheme-website'  => 'http://',
            'scheme-callback' => 'http://'
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
        $apps = self::$DI['app']['repos.api-applications']->findByCreator(self::$DI['user']);
        $nbApp = count($apps);

        self::$DI['client']->request('POST', '/developers/application/', [
            'type'            => ApiApplication::WEB_TYPE,
            'name'            => 'hello',
            'description'     => 'okok',
            'website'         => 'my.website.com',
            'callback'        => 'my.callback.com',
            'scheme-website'  => 'http://',
            'scheme-callback' => 'http://'
        ]);

        $apps = self::$DI['app']['repos.api-applications']->findByCreator(self::$DI['user']);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertGreaterThan($nbApp, count($apps));
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::getApp
     */
    public function testGetUnknowApp()
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
        self::$DI['client']->request('GET', '/developers/application/' . $oauthApp->get_id() . '/');
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
        $oauthApp = self::$DI['app']['manipulator.api-application']->create(
            'test app',
            '',
            '',
            'http://phraseanet.com/'
        );
        $this->XMLHTTPRequest('DELETE', '/developers/application/' . $oauthApp->getId() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $this->assertNull(self::$DI['app']['repos.api-application']->find($oauthApp->getId()));
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
        $this->XMLHTTPRequest('POST', '/developers/application/0/callback/', [
            'callback' => 'my.callback.com'
            ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::renewAppCallback
     */
    public function testRenewAppCallbackError2()
    {
        $oauthApp = self::$DI['oauth2-app-user'];
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
        $oauthApp = self::$DI['oauth2-app-user'];

        $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->get_id() . '/callback/', [
            'callback' => 'my.callback.com'
            ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue($content->success);
        $oauthApp = self::$DI['app']['repos.api-application']->find($oauthApp->getId());
        $this->assertEquals('my.callback.com', $oauthApp->getRedirectUri());
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
        $this->XMLHTTPRequest('POST', '/developers/application/0/access_token/', [
            'callback' => 'my.callback.com'
            ]);

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
        $oauthApp = self::$DI['oauth2-app-user'];

        $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->get_id() . '/access_token/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
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
        $this->XMLHTTPRequest('POST', '/developers/application/0/authorize_grant_password/', [
            'callback' => 'my.callback.com'
            ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertFalse($content->success);
    }

    /**
     * @cover \Alchemy\Phrasea\Controller\Root\Developers::authorizeGrantpassword
     */
    public function testAuthorizeGrantpasswordToken()
    {
        $oauthApp = self::$DI['oauth2-app-user'];

        $this->XMLHTTPRequest('POST', '/developers/application/' . $oauthApp->get_id() . '/authorize_grant_password/', [
            'grant' => '1'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue($content->success);
        $oauthApp = self::$DI['app']['repos.api-application']->find($oauthApp->getId());
        $this->assertTrue($oauthApp->isPasswordGranted());
    }
}
