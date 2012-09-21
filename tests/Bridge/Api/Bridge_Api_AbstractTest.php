<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/../Bridge_datas.inc';

class Bridge_Api_AbstractTest extends PhraseanetWebTestCaseAbstract
{
    public static $account = null;
    public static $api = null;
    protected $bridgeApi;

    /**
     * @var Bridge_Api_Abstract
     */
    protected $auth;

    public function setUp()
    {
        parent::setUp();
        $this->auth = $this->getMock("Bridge_Api_Auth_Interface");
        $this->bridgeApi = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(self::$application['phraseanet.registry'], $this->auth, "Mock_Bridge_Api_Abstract"));
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        try {
            self::$api = Bridge_Api::get_by_api_name(self::$application, 'apitest');
        } catch (Bridge_Exception_ApiNotFound $e) {
            self::$api = Bridge_Api::create(self::$application, 'apitest');
        }

        try {
            self::$account = Bridge_Account::load_account_from_distant_id(self::$application, self::$api, self::$user, 'kirikoo');
        } catch (Bridge_Exception_AccountNotFound $e) {
            self::$account = Bridge_Account::create(self::$application, self::$api, self::$user, 'kirikoo', 'coucou');
        }
    }

    public static function tearDownAfterClass()
    {
        self::$api->delete();
        if (self::$account instanceof Bridge_Account) {
            self::$account->delete();
        }
        parent::tearDownAfterClass();
    }

    public function testSet_auth_settings()
    {
        $settings = self::$account->get_settings();

        $this->bridgeApi->expects($this->once())
            ->method('set_transport_authentication_params');

        $return = $this->bridgeApi->set_auth_settings($settings);

        $this->assertEquals($this->bridgeApi, $return);
    }

    public function testConnectGood()
    {
        $this->bridgeApi->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(TRUE));

        $this->auth->expects($this->once())
            ->method('parse_request_token')
            ->will($this->returnValue("token"));
        $this->auth->expects($this->once())
            ->method('connect')
            ->will($this->returnValue(array("coucou")));

        $return = $this->bridgeApi->connect();

        $this->assertEquals(array("coucou"), $return);
    }

    public function testConnectBad()
    {
        $this->bridgeApi->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(FALSE));

        $this->setExpectedException("Bridge_Exception_ApiConnectorNotConfigured");

        $this->bridgeApi->connect();
    }

    public function testReconnect()
    {
        $this->bridgeApi->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(TRUE));

        $this->auth->expects($this->once())
            ->method('reconnect');

        $return = $this->bridgeApi->reconnect();

        $this->assertEquals($this->bridgeApi, $return);
    }

    public function testReconnectBad()
    {
        $this->bridgeApi->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(FALSE));

        $this->setExpectedException("Bridge_Exception_ApiConnectorNotConfigured");

        $this->bridgeApi->reconnect();
    }

    public function testDisconnect()
    {
        $this->bridgeApi->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(TRUE));

        $this->auth->expects($this->once())
            ->method('disconnect');

        $return = $this->bridgeApi->disconnect();

        $this->assertEquals($this->bridgeApi, $return);
    }

    public function testDisconnectBad()
    {
        $this->bridgeApi->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(FALSE));

        $this->setExpectedException("Bridge_Exception_ApiConnectorNotConfigured");

        $this->bridgeApi->disconnect();
    }

    public function testIs_connected()
    {
        $this->auth->expects($this->once())
            ->method('is_connected')
            ->will($this->returnValue(TRUE));

        $return = $this->bridgeApi->is_connected();

        $this->assertEquals(true, $return);
    }

    public function testGet_auth_url()
    {
        $this->auth->expects($this->once())
            ->method('get_auth_url')
            ->with($this->isType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY))
            ->will($this->returnValue("une url"));

        $return = $this->bridgeApi->get_auth_url();

        $this->assertEquals("une url", $return);
    }

    public function testSet_locale()
    {
        $this->bridgeApi->set_locale("fr");

        $this->assertEquals("fr", $this->bridgeApi->get_locale());
    }

    public function testIs_valid_object_id()
    {
        $this->assertTrue($this->bridgeApi->is_valid_object_id("abc"));
        $this->assertTrue($this->bridgeApi->is_valid_object_id(123));
        $this->assertTrue($this->bridgeApi->is_valid_object_id(12.25));
        $this->assertFalse($this->bridgeApi->is_valid_object_id(array()));
        $this->assertFalse($this->bridgeApi->is_valid_object_id(true));
    }

    public function testHandle_exception()
    {
        $e = new Exception("hihi");
        $void = $this->bridgeApi->handle_exception($e);
        $this->assertNull($void);
    }
}
