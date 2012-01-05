<?php

require_once dirname(__FILE__) . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . "/../../../../lib/bootstrap.php";
API_OAuth2_Autoloader::register();

use Symfony\Component\HttpFoundation\Response;
use Silex\WebTestCase;

/**
 * Test oauthv2 flow based on ietf authv2 spec
 * @link http://tools.ietf.org/html/draft-ietf-oauth-v2-18
 */

/**
 *
 *
 */
class oauthv2_application_test extends PhraseanetWebTestCaseAuthenticatedAbstract
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
    $parameters = array(
        'type' => 'web'
        , 'name' => 'test'
        , 'description' => 'une description'
        , 'callback' => 'http://callback.com/callback/'
        , 'website' => 'http://website.com/'
    );

    self::$appli = API_OAuth2_Application::create(appbox::get_instance(), self::$user, 'test');
    self::$appli->set_description('une description')
            ->set_redirect_uri('http://callback.com/callback/')
            ->set_website('http://website.com/')
            ->set_type(API_OAuth2_Application::WEB_TYPE);


  }

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
    if (self::$appli !== false)
      self::deleteInsertedRow(appbox::get_instance(), self::$appli);
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();

    $this->queryParameters = array(
        "response_type" => "code",
        "client_id" => self::$appli->get_client_id(),
        "redirect_uri" => self::$appli->get_redirect_uri(),
        "scope" => "",
        "state" => "valueTest"
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
    $appbox = appbox::get_instance();
    $conn = $appbox->get_connection();
    $stmt = $conn->prepare($sql);
    $stmt->execute($t);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result;
  }

    public static function getAccount()
  {
    $sql = "SELECT api_account_id FROM api_accounts WHERE application_id = :app_id AND usr_id = :usr_id";
    $t = array(":app_id" => self::$appli->get_id(), ":usr_id" => self::$user->get_id());
    $appbox = appbox::get_instance();
    $conn = $appbox->get_connection();
    $stmt = $conn->prepare($sql);
    $stmt->execute($t);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return new API_OAuth2_Account(appbox::get_instance(), $row["api_account_id"]);
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

  public function createApplication()
  {
    return require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/OAuth2.php';
  }

  public function testAuthorizeRedirect()
  {
    //session off
    $apps = API_OAuth2_Application::load_authorized_app_by_user(appbox::get_instance(), self::$user);
    foreach($apps as $app)
    {
      if($app->get_client_id() == self::$appli->get_client_id())
      {
        $authorize = true;

        $this->client->followRedirects();
      }
    }
  }

  public function testAuthorize()
  {
    $acc = self::getAccount();
    $acc->set_revoked(true); // revoked to show form

    $crawler = $this->client->request('GET', '/authorize', $this->queryParameters);
    $this->assertTrue($this->client->getResponse()->isSuccessful());
    $this->assertRegExp("/" . self::$appli->get_client_id() . "/", $this->client->getResponse()->getContent());
    $this->assertRegExp("/" . str_replace("/", '\/', self::$appli->get_redirect_uri()) . "/", $this->client->getResponse()->getContent());
    $this->assertRegExp("/" . $this->queryParameters["response_type"] . "/", $this->client->getResponse()->getContent());
    $this->assertRegExp("/" . $this->queryParameters["scope"] . "/", $this->client->getResponse()->getContent());
    $this->assertRegExp("/" . $this->queryParameters["state"] . "/", $this->client->getResponse()->getContent());
  }
}
