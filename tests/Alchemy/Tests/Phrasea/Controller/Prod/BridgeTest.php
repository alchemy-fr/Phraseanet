<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

require_once __DIR__ . '/../../../../../classes/Bridge/Bridge_datas.inc';

class BridgeTest extends \PhraseanetAuthenticatedWebTestCase
{
    public static $account = null;
    public static $api = null;
    protected $client;

    public function setUp()
    {
        parent::setUp();
        try {
            self::$api = \Bridge_Api::get_by_api_name(self::$DI['app'], 'apitest');
        } catch (\Bridge_Exception_ApiNotFound $e) {
            self::$api = \Bridge_Api::create(self::$DI['app'], 'apitest');
        }

        try {
            self::$account = \Bridge_Account::load_account_from_distant_id(self::$DI['app'], self::$api, self::$DI['user'], 'kirikoo');
        } catch (\Bridge_Exception_AccountNotFound $e) {
            self::$account = \Bridge_Account::create(self::$DI['app'], self::$api, self::$DI['user'], 'kirikoo', 'coucou');
        }
    }

    public function tearDown()
    {
        if (self::$api instanceof \Bridge_Api) {
            self::$api->delete();
        }
        if (self::$account instanceof \Bridge_Account) {
            self::$account->delete();
        }
        parent::tearDown();
    }

    public static function tearDownAfterClass()
    {
        self::$api = self::$account = null;
        parent::tearDownAfterClass();
    }

    /**
     * @todo create a new basket dont take an existing one
     */
    public function testManager()
    {
        self::$DI['client']->request('POST', '/prod/bridge/manager/', ['ssel' => 1]);
        self::$DI['client']->getResponse()->getContent();
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testLogin()
    {
        self::$DI['client']->request('GET', '/prod/bridge/login/Apitest/');
        $test = new \Bridge_Api_Apitest(self::$DI['app']['url_generator'], self::$DI['app']['conf'], new \Bridge_Api_Auth_None(), self::$DI['app']['translator']);
        $this->assertTrue(self::$DI['client']->getResponse()->getStatusCode() == 302);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect($test->get_auth_url()));
    }

    public function testCallBackFailed()
    {
        $crawler = self::$DI['client']->request('GET', '/prod/bridge/callback/unknow_api/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testCallBackAccountAlreadyDefined()
    {
        $crawler = self::$DI['client']->request('GET', '/prod/bridge/callback/apitest/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $pageContent = self::$DI['client']->getResponse()->getContent();
        //check for errors in the crawler
        $crawler
            ->filter('div')
            ->reduce(function ($crawler, $i) {
                if (!$crawler->attr('class')) {
                    return false;
                } elseif ($node->getAttribute('class') == 'error_auth') {
                    $this->fail("Erreur callback");
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
        if (self::$account instanceof \Bridge_Account)
            self::$account->delete();
        $crawler = self::$DI['client']->request('GET', '/prod/bridge/callback/apitest/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $crawler
            ->filter('div')
            ->reduce(function ($crawler, $i) {
                if (!$crawler->attr('class')) {
                    return false;
                } elseif ($node->getAttribute('class') == 'error_auth') {
                    $this->fail("Erreur callback");
                }
            });
        try {
            self::$account = \Bridge_Account::load_account_from_distant_id(self::$DI['app'], self::$api, self::$DI['user'], 'kirikoo');
            $settings = self::$account->get_settings();
            $this->assertEquals("kikoo", $settings->get("auth_token"));
            $this->assertEquals("kooki", $settings->get("refresh_token"));
            $this->assertEquals("biloute", $settings->get("access_token"));
            $settings->delete("auth_token");
            $settings->delete("refresh_token");
            $settings->delete("access_token");
        } catch (\Bridge_Exception_AccountNotFound $e) {
            $this->fail("No account created after callback");
        }

        if ( ! self::$account instanceof \Bridge_Account)
            self::$account = \Bridge_Account::create(self::$DI['app'], self::$api, self::$DI['user'], 'kirikoo', 'coucou');
    }

    public function testLogout()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf('/prod/bridge/adapter/%d/logout/', self::$account->get_id());
        self::$DI['client']->request('GET', $url);
        $redirect = sprintf("/prod/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect($redirect));
        $this->assertNull(self::$account->get_settings()->get("auth_token"));
    }

    public function testLoadElements()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $account = new \Bridge_Account(self::$DI['app'], self::$api, self::$account->get_id());
        $crawler = self::$DI['client']->request('GET', $url, ["page" => 1]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertNotContains(self::$account->get_api()->generate_login_url(self::$DI['app']['url_generator'], self::$account->get_api()->get_connector()->get_name()), self::$DI['client']->getResponse()->getContent());
    }

    public function testLoadRecords()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/adapter/%s/load-records/", self::$account->get_id());
        $crawler = self::$DI['client']->request('GET', $url, ["page"    => 1]);
        $elements = \Bridge_Element::get_elements_by_account(self::$DI['app'], self::$account);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(sizeof($elements), $crawler->filterXPath("//table/tr")->count());
        $this->assertNotContains(self::$account->get_api()->generate_login_url(self::$DI['app']['url_generator'], self::$account->get_api()->get_connector()->get_name()), self::$DI['client']->getResponse()->getContent());
    }

    public function testLoadRecordsDisconnected()
    {
        self::$DI['client']->followRedirects();
        self::$account->get_settings()->set("auth_token", null); //deconnected
        $url = sprintf("/prod/bridge/adapter/%s/load-records/", self::$account->get_id());
        $crawler = self::$DI['client']->request('GET', $url, ["page"       => 1]);
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertContains($url, $pageContent);
        $this->deconnected($crawler, $pageContent);
    }

    public function testLoadContainers()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/adapter/%s/load-containers/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
        $crawler = self::$DI['client']->request('GET', $url, ["page"    => 1]);
        $elements = \Bridge_Element::get_elements_by_account(self::$DI['app'], self::$account);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertNotContains(self::$account->get_api()->generate_login_url(self::$DI['app']['url_generator'], self::$account->get_api()->get_connector()->get_name()), self::$DI['client']->getResponse()->getContent());
    }

    public function testLoadContainersDisconnected()
    {
        self::$DI['client']->followRedirects();
        self::$account->get_settings()->set("auth_token", null); //deconnected
        $url = sprintf("/prod/bridge/adapter/%s/load-containers/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
        $crawler = self::$DI['client']->request('GET', $url, ["page"       => 1]);
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertContains($url, $pageContent);
        $this->deconnected($crawler, $pageContent);
    }

    public function testLoadElementsDisconnected()
    {
        self::$DI['client']->followRedirects();
        self::$account->get_settings()->set("auth_token", null); //deconnected
        $url = sprintf("/prod/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $crawler = self::$DI['client']->request('GET', $url, ["page"       => 1]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertContains($url, $pageContent);
        $this->deconnected($crawler, $pageContent);
    }

    public function testLogoutDeconnected()
    {
        self::$DI['client']->followRedirects();
        self::$account->get_settings()->set("auth_token", null); //deconnected
        $url = sprintf('/prod/bridge/adapter/%d/logout/', self::$account->get_id());
        $crawler = self::$DI['client']->request('GET', $url);
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertContains("/adapter/" . self::$account->get_id() . "/logout/", $pageContent);
        $this->deconnected($crawler, $pageContent);
    }

    public function testActionDeconnected()
    {
        self::$DI['client']->followRedirects();
        self::$account->get_settings()->set("auth_token", null); //deconnected
        $url = sprintf("/prod/bridge/action/%s/une action/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $crawler = self::$DI['client']->request('GET', $url);
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertContains($url, $pageContent);
        $this->deconnected($crawler, $pageContent);
    }

    public function testActionUnknow()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/action/%s/ajjfhfjozqd/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        try {
            $crawler = self::$DI['client']->request('GET', $url, ["elements_list" => "1;2;3"]);
            $this->fail("expected Exception here");
        } catch (\Exception $e) {

        }

        try {
            $crawler = self::$DI['client']->request('POST', $url, ["elements_list" => "1;2;3"]);
            $this->fail("expected Exception here");
        } catch (\Exception $e) {

        }
    }

    public function testActionModifyTooManyElements()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $crawler = self::$DI['client']->request('GET', $url, ["element_list" => "1_2;1_3;1_4"]);
        $redirect = sprintf("/prod/bridge/adapter/%s/load-elements/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertContains($redirect, self::$DI['client']->getResponse()->headers->get("location"));
        $this->assertContains("error=", self::$DI['client']->getResponse()->headers->get("location"));
        $this->assertNotContains(self::$account->get_api()->generate_login_url(self::$DI['app']['url_generator'], self::$account->get_api()->get_connector()->get_name()), self::$DI['client']->getResponse()->getContent());

        self::$DI['client']->request('POST', $url, ["element_list" => "1_2;1_3;1_4"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testActionModifyElement()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $crawler = self::$DI['client']->request('GET', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertNotContains(self::$account->get_api()->generate_login_url(self::$DI['app']['url_generator'], self::$account->get_api()->get_connector()->get_name()), self::$DI['client']->getResponse()->getContent());

        self::$DI['client']->request('POST', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testActionModifyElementError()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull");
        \Bridge_Api_Apitest::$hasError = true;
        $url = sprintf("/prod/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        self::$DI['client']->request('POST', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testActionModifyElementException()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull");
        \Bridge_Api_Apitest::$hasException = true;
        $url = sprintf("/prod/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        self::$DI['client']->request('POST', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testActionDeleteElement()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull");
        $url = sprintf("/prod/bridge/action/%s/deleteelement/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        self::$DI['client']->request('GET', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        \Bridge_Api_Apitest::$hasException = true;
        $url = sprintf("/prod/bridge/action/%s/deleteelement/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        self::$DI['client']->request('POST', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error/', self::$DI['client']->getResponse()->headers->get('location'));

        $url = sprintf("/prod/bridge/action/%s/deleteelement/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        self::$DI['client']->request('POST', $url, ["elements_list" => "element123qcs789"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testActionCreateContainer()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected

        $url = sprintf("/prod/bridge/action/%s/createcontainer/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
        self::$DI['client']->request('GET', $url);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        \Bridge_Api_Apitest::$hasException = true;
        $url = sprintf("/prod/bridge/action/%s/createcontainer/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        self::$DI['client']->request('POST', $url);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/error/', self::$DI['client']->getResponse()->headers->get('location'));

        $url = sprintf("/prod/bridge/action/%s/createcontainer/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
        self::$DI['client']->request('POST', $url, ['title'       => 'test', 'description' => 'description']);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $this->assertRegexp('/success/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    /**
     * @todo no templates declared for modify a container in any apis
     */
    public function testActionModifyContainer()
    {
        $this->markTestSkipped("No templates declared for modify a container in any apis");
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/action/%s/modify/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_container_type());
        $crawler = self::$DI['client']->request('GET', $url, ["elements_list" => "containerudt456shn"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertNotContains(self::$account->get_api()->generate_login_url(self::$DI['app']['url_generator'], self::$account->get_api()->get_connector()->get_name()), self::$DI['client']->getResponse()->getContent());

        self::$DI['client']->request('POST', $url, ["elements_list" => "containerudt456shn"]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testActionMoveInto()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull"); //connected
        $url = sprintf("/prod/bridge/action/%s/moveinto/%s/", self::$account->get_id(), self::$account->get_api()->get_connector()->get_default_element_type());
        $crawler = self::$DI['client']->request('GET', $url, ["elements_list" => "containerudt456shn", 'destination'   => self::$account->get_api()->get_connector()->get_default_container_type()]);
        $this->assertNotContains("http://dev.phrasea.net/prod/bridge/login/youtube/", self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        self::$DI['client']->request('POST', $url, ["elements_list" => "containerudt456shn", 'destination'   => self::$account->get_api()->get_connector()->get_default_container_type()]);
        $this->assertRegexp('/success/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        \Bridge_Api_Apitest::$hasException = true;
        self::$DI['client']->request('POST', $url, ["elements_list" => "containerudt456shn", 'destination'   => self::$account->get_api()->get_connector()->get_default_container_type()]);
        $this->assertRegexp('/error/', self::$DI['client']->getResponse()->headers->get('location'));
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function deconnected($crawler, $pageContent)
    {
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertContains("prod/bridge/login/" . mb_strtolower(self::$account->get_api()->get_connector()->get_name()) . "/", $pageContent);
    }

    public function testUpload()
    {
        self::$account->get_settings()->set("auth_token", "somethingNotNull");
        self::$DI['client']->request('GET', "/prod/bridge/upload/", ["account_id" => self::$account->get_id(), 'lst' => self::$DI['record_1']->get_serialize_key()]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testDeleteAccount()
    {
        $account = \Bridge_Account::create(self::$DI['app'], self::$api, self::$DI['app']['authentication']->getUser(), 'hello', 'you');
        $url = "/prod/bridge/adapter/" . $account->get_id() . "/delete/";
        self::$DI['client']->request('POST', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        try {
            \Bridge_Account::load_account(self::$DI['app'], $account->get_id());
            $this->fail('Account is not deleted');
        } catch (\Bridge_Exception_AccountNotFound $e) {

        }
        unset($account, $response);
    }
}
