<?php

require_once dirname(__FILE__) . '/../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once dirname(__FILE__) . '/Bridge_datas.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class Bridge_Application extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  public static $account = null;
  public static $api = null;
  protected $client;

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
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

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
    self::$api->delete();
    if (self::$account instanceof Bridge_Account)
      self::$account->delete();
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  public function createApplication()
  {
    $app = require __DIR__ . '/../../Alchemy/Phrasea/Application/Prod.php';

    return $app;
  }

  /**
   * @todo create a new basket dont take an existing one
   */
  public function testManager()
  {
    $appbox = appbox::get_instance();
    $accounts = Bridge_Account::get_accounts_by_user($appbox, self::$user);
    $usr_id = self::$user->get_id();

    try
    {
      $basket_coll = new basketCollection($appbox, $usr_id);
      $baskets = $basket_coll->get_baskets();
      if (count($baskets["baskets"]) > 0)
      {
        $basket = array_shift($baskets["baskets"]);
        $crawler = $this->client->request('POST', '/bridge/manager/', array('ssel' => $basket->get_ssel_id()));
        $pageContent = $this->client->getResponse()->getContent();
        $this->assertNotContains("Oups ! something went wrong !", $pageContent);
        $this->assertEquals(count($accounts) + 2, $crawler->filter('form')->count());
        $this->assertTrue($this->client->getResponse()->isOk());
      }
    }
    catch (Exception $e)
    {

    }
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
    $this->assertNotContains("Oups ! something went wrong !", $pageContent);
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
    try
    {
    $url = sprintf('/bridge/adapter/%d/logout/', self::$account->get_id());
    $crawler = $this->client->request('GET', $url);
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains("/adapter/" . self::$account->get_id() . "/logout/", $pageContent);
    $this->deconnected($crawler, $pageContent);
    }
    catch(Exception $e)
    {
      exit($e);
    }
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
    $url = sprintf("/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_name());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals(5, $crawler->filterXPath("//div[@class='element']")->count());
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertNotContains("Oups ! something went wrong !", $pageContent);
    self::$account->get_settings()->set("auth_token", null); //disconnected
  }

  public function testLoadElementsDisconnected()
  {
    $url = sprintf("/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_name());
    $crawler = $this->client->request('GET', $url, array("page" => 1));
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
    self::$account->get_settings()->set("auth_token", null); //disconnected
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
    $this->assertEquals(5, $crawler->filterXPath("//div[@class='element']")->count());
    self::$account->get_settings()->set("auth_token", null); //disconnected
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
    self::$account->get_settings()->set("auth_token", null); //disconnected
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
    self::$account->get_settings()->set("auth_token", null); //disconnected
  }

  public function testActionModifyElement()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url, array("elements_list" => "element123qcs789"));
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertContains("element123qcs789", $pageContent);
    $this->assertNotContains("Oups ! something went wrong !", $pageContent);
    self::$account->get_settings()->set("auth_token", null); //disconnected
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
    self::$account->get_settings()->set("auth_token", null); //disconnected
  }

  public function testActionMoveInto()
  {
    self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
    $url = sprintf("/bridge/action/%s/moveinto/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
    $crawler = $this->client->request('GET', $url, array("elements_list" => "containerudt456shn", 'destination' => self::$account->get_api()->get_connector()->get_default_container_type()));
    $this->assertTrue($this->client->getResponse()->isOk());
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertContains("containerudt456shn", $pageContent);
    $this->assertNotContains("Oups ! something went wrong !", $pageContent);
    self::$account->get_settings()->set("auth_token", null); //disconnected
  }

  public function deconnected($crawler, $pageContent)
  {
    $this->assertNotContains("Oups ! something went wrong !", $pageContent);

    $this->assertTrue($this->client->getResponse()->isOk());

    $this->assertContains("/prod/bridge/login/" . mb_strtolower(self::$account->get_api()->get_connector()->get_name()), $pageContent);

    $this->assertEquals(2, $crawler->filter("form")->count());
  }

}
