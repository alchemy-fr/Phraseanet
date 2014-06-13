<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Model\Entities\ApiApplication;

class OAuth2Test extends \PhraseanetAuthenticatedWebTestCase
{
    protected $queryParameters;

    public function setUp()
    {
        parent::setUp();

        self::$DI['app'] = self::$DI->share(function ($DI) {
            return $this->loadApp('/lib/Alchemy/Phrasea/Application/Api.php');
        });

        $this->queryParameters = [
            "response_type" => "code",
            "client_id"     => self::$DI['oauth2-app-user']->getClientId(),
            "redirect_uri"  => self::$DI['oauth2-app-user']->getRedirectUri(),
            "scope"         => "",
            "state"         => "valueTest"
        ];
    }

    /**
     * @dataProvider provideEventNames
     */
    public function testThatEventsAreTriggered($revoked, $method, $eventName, $className)
    {
        if ($revoked) {
            self::$DI['app']['manipulator.api-account']->revokeAccess(self::$DI['oauth2-app-acc-user']);
        } else {
            self::$DI['app']['manipulator.api-account']->authorizeAccess(self::$DI['oauth2-app-acc-user']);
        }

        $preEvent = 0;
        self::$DI['app']['dispatcher']->addListener($eventName, function ($event) use (&$preEvent, $className) {
            $preEvent++;
            $this->assertInstanceOf($className, $event);
            $this->assertEquals(Context::CONTEXT_OAUTH2_NATIVE, $event->getContext()->getContext());
        });

        self::$DI['client']->request($method, '/api/oauthv2/authorize', $this->queryParameters);

        $this->assertEquals(1, $preEvent);
    }

    public function provideEventNames()
    {
        return [
            [false, 'POST', PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate'],
            [true, 'POST', PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate'],
            [false, 'GET', PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate'],
            [true, 'GET', PhraseaEvents::PRE_AUTHENTICATE, 'Alchemy\Phrasea\Core\Event\PreAuthenticate'],
        ];
    }

    public function setQueryParameters($parameter, $value)
    {
        $this->queryParameters[$parameter] = $value;
    }

    public function unsetQueryParameter($parameter)
    {
        if (isset($this->queryParameters[$parameter])) {
            unset($this->queryParameters[$parameter]);
        }
    }

    public function testAuthorizeRedirect()
    {
        //session off
        $apps = self::$DI['app']['repo.api-applications']->findAuthorizedAppsByUser(self::$DI['user']);
        foreach ($apps as $app) {
            if ($app->getClientId() === self::$DI['oauth2-app-user']->getClientId()) {
                self::$DI['client']->followRedirects();
            }
        }
    }

    public function testAuthorize()
    {
        self::$DI['app']['manipulator.api-account']->revokeAccess(self::$DI['oauth2-app-acc-user']);
        self::$DI['client']->request('GET', '/api/oauthv2/authorize', $this->queryParameters);
        $this->assertTrue(self::$DI['client']->getResponse()->isSuccessful());
        $this->assertRegExp("/" . self::$DI['oauth2-app-user']->getCLientId() . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . str_replace("/", '\/', self::$DI['oauth2-app-user']->getRedirectUri()) . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . $this->queryParameters["response_type"] . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . $this->queryParameters["scope"] . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . $this->queryParameters["state"] . "/", self::$DI['client']->getResponse()->getContent());
    }

    public function testGetTokenNotHTTPS()
    {
        $this->setQueryParameters('grant_type', 'authorization_code');
        $this->setQueryParameters('code', '12345678918');
        self::$DI['client']->request('POST', '/api/oauthv2/token', $this->queryParameters);
        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());
    }
}
