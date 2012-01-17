<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../../../Bridge/Bridge_datas.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BridgeApplication extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  public static $account = null;
  public static $api = null;
  protected $client;

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
  }

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
    try
    {
      self::$api = Bridge_Api::get_by_api_name(appbox::get_instance(), 'apitest');
    }
    catch (Bridge_Exception_ApiNotFound $e)
    {
      self::$api = Bridge_Api::create(appbox::get_instance(), 'apitest');
    }

    try
    {
      self::$account = Bridge_Account::load_account_from_distant_id(appbox::get_instance(), self::$api, self::$user, 'kirikoo');
    }
    catch (Bridge_Exception_AccountNotFound $e)
    {
      self::$account = Bridge_Account::create(appbox::get_instance(), self::$api, self::$user, 'kirikoo', 'coucou');
    }
  }

  public function tearDown()
  {
    parent::tearDown();
    self::$api->delete();
    if (self::$account instanceof Bridge_Account)
      self::$account->delete();
  }

  public function createApplication()
  {
    return include realpath(__DIR__ . '/../../../../../Alchemy/Phrasea/Application/Prod.php');
  }

  /**
   * @todo create a new basket dont take an existing one
   */
  public function testManager()
  {
    $appbox = appbox::get_instance();
    $accounts = Bridge_Account::get_accounts_by_user($appbox, self::$user);
    $usr_id = self::$user->get_id();

    $basket = $this->insertOneBasket();

    $crawler = $this->client->request('POST', '/bridge/manager/', array('ssel' => $basket->getId()));
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());
  }

  public function testLogin()
  {
    $this->client->request('GET', '/bridge/login/Apitest/');
    $test = new Bridge_Api_Apitest(registry::get_instance(), new Bridge_Api_Auth_None());
    $this->assertTrue($this->client->getResponse()->getStatusCode() == 302);
    $this->assertTrue($this->client->getResponse()->isRedirect($test->get_auth_url()));
  }

  public function testCallBackAccountAlreadyDefined()
  {
    $appbox = appbox::get_instance();
    $crawler = $this->client->request('GET', '/bridge/callback/apitest/');
    $this->assertTrue($this->client->getResponse()->isOk());
    $pageContent = $this->client->getResponse()->getContent();
    //check for errors in the crawler
    $phpunit = $this;
    $crawler
            ->filter('div')
            ->reduce(function ($node, $i) use ($phpunit)
                    {
                      if (!$node->getAttribute('class'))
                      {
                        return false;
                      }
                      elseif ($node->getAttribute('class') == 'error_auth')
                      {
                        $phpunit->fail("Erreur callback");
                      }
                    });
    $settings = self::$account->get_settings();
    $this->assertEquals("kikoo", $settings->get("auth_token"));
    $this->assertEquals("kooki", $settings->get("refresh_token"));
    $this->assertEquals("biloute", $settings->get("access_token"));
    $settings->delete("auth_token");
    $settings->delete("refresh_token");
    $settings->delete("access_token");
  }

  public function testCallBackAccountNoDefined()
  {
    if (self::$account instanceof Bridge_Account)
      self::$account->delete();
    $crawler = $this->client->request('GET', '/bridge/callback/apitest/');
    $this->assertTrue($this->client->getResponse()->isOk());
    $phpunit = $this;
    $crawler
            ->filter('div')
            ->reduce(function ($node, $i) use ($phpunit)
                    {
                      if (!$node->getAttribute('class'))
                      {
                        return false;
                      }
                      elseif ($node->getAttribute('class') == 'error_auth')
                      {
                        $phpunit->fail("Erreur callback");
                      }
                    });
    try
    {
      self::$account = Bridge_Account::load_account_from_distant_id(appbox::get_instance(), self::$api, self::$user, 'kirikoo');
      $settings = self::$account->get_settings();
      $this->assertEquals("kikoo", $settings->get("auth_token"));
      $this->assertEquals("kooki", $settings->get("refresh_token"));
      $this->assertEquals("biloute", $settings->get("access_token"));
      $settings->delete("auth_token");
      $settings->delete("refresh_token");
      $settings->delete("access_token");
    }
    catch (Bridge_Exception_AccountNotFound $e)
    {
      $this->fail("No account created after callback");
    }

    if (!self::$account instanceof Bridge_Account)
      self::$account = Bridge_Account::create(appbox::get_instance(), self::$api, self::$user, 'kirikoo', 'coucou');
  }

  public function testLogoutDeconnected()
  {
    $url = sprintf('/bridge/adapter/%d/logout/', self::$account->get_id());
    $crawler = $this->client->request('GET', $url);
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains("/adapter/" . self::$account->get_id() . "/logout/", $pageContent);
    $this->deconnected($crawler, $pageContent);
  }

  public function testLogout()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf('/bridge/adapter/%d/logout/', self::$account->get_id());
    $this->client->request('GET', $url);
    $redirect = sprintf("/prod/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $this->assertTrue($this->client->getResponse()->isRedirect($redirect));
    $this->assertNull(self::$account->get_settings()->get("auth_token"));
  }

  public function testLoadElements()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $account = new Bridge_Account(appbox::get_instance(), self::$api, self::$account->get_id());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertNotContains(self::$account->get_api()->generate_login_url(registry::get_instance(), self::$account->get_api()->get_connector()->get_name()), $this->client->getResponse()->getContent());
  }

  public function testLoadElementsDisconnected()
  {
    $url = sprintf("/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $this->assertTrue($this->client->getResponse()->isOk());
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains($url, $pageContent);
    $this->deconnected($crawler, $pageContent);
  }

  public function testLoadRecords()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/adapter/%s/load-records/", self::$account->get_id());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $elements = Bridge_Element::get_elements_by_account(appbox::get_instance(), self::$account);
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals(sizeof($elements), $crawler->filterXPath("//table/tr")->count());
    $this->assertNotContains(self::$account->get_api()->generate_login_url(registry::get_instance(), self::$account->get_api()->get_connector()->get_name()), $this->client->getResponse()->getContent());
  }

  public function testLoadRecordsDisconnected()
  {
    $url = sprintf("/bridge/adapter/%s/load-records/", self::$account->get_id());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains($url, $pageContent);
    $this->deconnected($crawler, $pageContent);
  }

  public function testLoadContainers()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/adapter/%s/load-containers/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $elements = Bridge_Element::get_elements_by_account(appbox::get_instance(), self::$account);
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertNotContains(self::$account->get_api()->generate_login_url(registry::get_instance(), self::$account->get_api()->get_connector()->get_name()), $this->client->getResponse()->getContent());
  }

  public function testLoadContainersDisconnected()
  {
    $url = sprintf("/bridge/adapter/%s/load-containers/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains($url, $pageContent);
    $this->deconnected($crawler, $pageContent);
  }

  public function testActionDeconnected()
  {
    $url = sprintf("/bridge/action/%s/une action/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url);
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains($url, $pageContent);
    $this->deconnected($crawler, $pageContent);
  }

  public function testActionUnknow()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/ajjfhfjozqd/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    try
    {
      $crawler = $this->client->request('GET', $url, array("elements_list" => "1;2;3"));
      $this->fail("expected Exception here");
    }
    catch (Exception $e)
    {
      
    }
  }

  public function testActionModifyTooManyElements()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url, array("element_list" => "1_2;1_3;1_4"));
    $redirect = sprintf("/prod/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $this->assertTrue($this->client->getResponse()->isRedirect());
    $this->assertContains($redirect, $this->client->getResponse()->headers->get("location"));
    $this->assertContains("error=", $this->client->getResponse()->headers->get("location"));
    $this->assertNotContains(self::$account->get_api()->generate_login_url(registry::get_instance(), self::$account->get_api()->get_connector()->get_name()), $this->client->getResponse()->getContent());
  }

  public function testActionModifyElement()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url, array("elements_list" => "element123qcs789"));
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertNotContains(self::$account->get_api()->generate_login_url(registry::get_instance(), self::$account->get_api()->get_connector()->get_name()), $this->client->getResponse()->getContent());
  }

  /**
   * @todo no templates declared for modify a container in any apis
   */
  public function testActionModifyContainer()
  {
    $this->markTestSkipped("No templates declared for modify a container in any apis");
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
    $crawler = $this->client->request('GET', $url, array("elements_list" => "containerudt456shn"));
    $this->assertTrue($this->client->getResponse()->isOk());
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertNotContains(self::$account->get_api()->generate_login_url(registry::get_instance(), self::$account->get_api()->get_connector()->get_name()), $this->client->getResponse()->getContent());
  }

  public function testActionMoveInto()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/moveinto/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url, array("elements_list" => "containerudt456shn", 'destination' => self::$account->get_api()->get_connector()->get_default_container_type()));
    $this->assertNotContains("http://dev.phrasea.net/prod/bridge/login/youtube/", $this->client->getResponse()->getContent());
    $this->assertTrue($this->client->getResponse()->isOk());
  }

  public function deconnected($crawler, $pageContent)
  {

    $this->assertTrue($this->client->getResponse()->isOk());

    $this->assertContains("prod/bridge/login/" . mb_strtolower(self::$account->get_api()->get_connector()->get_name())."/", $pageContent);
  }

}
