<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Model\Entities\ApiApplication;

/**
 * Test oauthv2 flow based on ietf authv2 spec
 * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-18
 */
class OAuth2Test extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     *
     * @var ApiApplication
     */
    public static $account_id;
    public static $account;
    public $oauth;
    protected $client;
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
            "redirect_uri"  => self::$DI['oauth2-app-user']->getRedirectId(),
            "scope"         => "",
            "state"         => "valueTest"
        ];
    }

    public static function tearDownAfterClass()
    {
        self::$account_id = self::$account = null;
        parent::tearDownAfterClass();
    }

    public static function deleteInsertedRow(\appbox $appbox, ApiApplication $application)
    {
        self::$DI['app']['manipulator.api-application']->delete($application);
    }

    /**
     * @dataProvider provideEventNames
     */
    public function testThatEventsAreTriggered($revoked, $method, $eventName, $className)
    {
        $acc = self::getAccount();
        $acc->set_revoked($revoked); // revoked to show form

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

    public static function getApp($rowId)
    {
        $sql = "SELECT * FROM api_applications WHERE application_id = :app_id";
        $t = [":app_id" => $rowId];
        $conn = self::$DI['app']['phraseanet.appbox']->get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($t);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $result;
    }

    public static function getAccount()
    {
        return self::$DI['app']['repo.api-accounts']->findByUserAndApplication(self::$DI['user'], self::$DI['oauth2-app-user']);
    }

    public function setQueryParameters($parameter, $value)
    {
        $this->queryParameters[$parameter] = $value;
    }

    public function unsetQueryParameter($parameter)
    {
        if (isset($this->queryParameters[$parameter]))
            unset($this->queryParameters[$parameter]);
    }

    public function testAuthorizeRedirect()
    {
        //session off
        $apps = self::$DI['app']['repo.api-application']->findAuthorizedAppsByUser(self::$DI['user']);
        foreach ($apps as $app) {
            if ($app->get_client_id() === self::$DI['oauth2-app-user']->getClientId()) {
                self::$DI['client']->followRedirects();
            }
        }
    }

    public function testAuthorize()
    {
        $acc = self::getAccount();
        $acc->set_revoked(true); // revoked to show form

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
