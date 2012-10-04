<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

/**
 * Test oauthv2 flow based on ietf authv2 spec
 * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-18
 */
class oauthv2_application_test extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     *
     * @var API_OAuth2_Application
     */
    public static $appli;
    public static $account_id;
    public static $account;
    public $oauth;
    protected $client;
    protected $queryParameters;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $environment = 'test';
        $application = require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/OAuth2.php';

        self::$appli = API_OAuth2_Application::create($application, self::$DI['user'], 'test');
        self::$appli->set_description('une description')
            ->set_redirect_uri('http://callback.com/callback/')
            ->set_website('http://website.com/')
            ->set_type(API_OAuth2_Application::WEB_TYPE);
    }

    public static function tearDownAfterClass()
    {
        if (self::$appli !== false) {
            self::deleteInsertedRow(self::$DI['app']['phraseanet.appbox'], self::$appli);
        }
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        parent::setUp();

        $environment = 'test';
        self::$DI['app'] = require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/OAuth2.php';

        $this->queryParameters = array(
            "response_type" => "code",
            "client_id"     => self::$appli->get_client_id(),
            "redirect_uri"  => self::$appli->get_redirect_uri(),
            "scope"         => "",
            "state"         => "valueTest"
        );
    }

    public static function deleteInsertedRow(appbox $appbox, API_OAuth2_Application $app)
    {
        $conn = $appbox->get_connection();
        $sql = '
      DELETE FROM api_applications
      WHERE application_id = :id
    ';
        $t = array(':id' => $app->get_id());
        $stmt = $conn->prepare($sql);
        $stmt->execute($t);
        $sql = '
      DELETE FROM api_accounts
      WHERE api_account_id  = :id
    ';
        $acc = self::getAccount();
        $t = array(':id' => $acc->get_id());
        $stmt = $conn->prepare($sql);
        $stmt->execute($t);
    }

    public static function getApp($rowId)
    {
        $sql = "SELECT * FROM api_applications WHERE application_id = :app_id";
        $t = array(":app_id" => $rowId);
        $conn = self::$DI['app']['phraseanet.appbox']->get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($t);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    public static function getAccount()
    {
        $sql = "SELECT api_account_id FROM api_accounts WHERE application_id = :app_id AND usr_id = :usr_id";
        $t = array(":app_id" => self::$appli->get_id(), ":usr_id" => self::$DI['user']->get_id());
        $conn = self::$DI['app']['phraseanet.appbox']->get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($t);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return new API_OAuth2_Account(self::$DI['app'], $row["api_account_id"]);
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
        $apps = API_OAuth2_Application::load_authorized_app_by_user(self::$DI['app'], self::$DI['user']);
        foreach ($apps as $app) {
            if ($app->get_client_id() == self::$appli->get_client_id()) {
                $authorize = true;

                self::$DI['client']->followRedirects();
            }
        }
    }

    public function testAuthorize()
    {
        $acc = self::getAccount();
        $acc->set_revoked(true); // revoked to show form

        $crawler = self::$DI['client']->request('GET', '/authorize', $this->queryParameters);
        $this->assertTrue(self::$DI['client']->getResponse()->isSuccessful());
        $this->assertRegExp("/" . self::$appli->get_client_id() . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . str_replace("/", '\/', self::$appli->get_redirect_uri()) . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . $this->queryParameters["response_type"] . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . $this->queryParameters["scope"] . "/", self::$DI['client']->getResponse()->getContent());
        $this->assertRegExp("/" . $this->queryParameters["state"] . "/", self::$DI['client']->getResponse()->getContent());
    }
}
