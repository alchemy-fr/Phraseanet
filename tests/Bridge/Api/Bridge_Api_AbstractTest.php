<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../Bridge_datas.inc';

class Bridge_Api_AbstractTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    public static $account = null;
    public static $api = null;

    /**
     * @var Bridge_Api_Abstract
     */
    protected $auth;

    public function setUp()
    {
        parent::setUp();
        $this->auth = $this->getMock("Bridge_Api_Auth_Interface");
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        try {
            self::$api = Bridge_Api::get_by_api_name(appbox::get_instance(\bootstrap::getCore()), 'apitest');
        } catch (Bridge_Exception_ApiNotFound $e) {
            self::$api = Bridge_Api::create(appbox::get_instance(\bootstrap::getCore()), 'apitest');
        }

        try {
            self::$account = Bridge_Account::load_account_from_distant_id(appbox::get_instance(\bootstrap::getCore()), self::$api, self::$user, 'kirikoo');
        } catch (Bridge_Exception_AccountNotFound $e) {
            self::$account = Bridge_Account::create(appbox::get_instance(\bootstrap::getCore()), self::$api, self::$user, 'kirikoo', 'coucou');
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
        $stub = $this->getMockForAbstractClass('Bridge_Api_Abstract', array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $settings = self::$account->get_settings();

        $stub->expects($this->once())
            ->method('set_transport_authentication_params');

        $return = $stub->set_auth_settings($settings);

        $this->assertEquals($stub, $return);
    }

    public function testConnectGood()
    {
        $stub = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(TRUE));

        $this->auth->expects($this->once())
            ->method('parse_request_token')
            ->will($this->returnValue("token"));
        $this->auth->expects($this->once())
            ->method('connect')
            ->will($this->returnValue(array("coucou")));

        $return = $stub->connect();

        $this->assertEquals(array("coucou"), $return);
    }

    public function testConnectBad()
    {
        $stub = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(FALSE));

        $this->setExpectedException("Bridge_Exception_ApiConnectorNotConfigured");

        $stub->connect();
    }

    public function testReconnect()
    {
        $stub = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(TRUE));

        $this->auth->expects($this->once())
            ->method('reconnect');

        $return = $stub->reconnect();

        $this->assertEquals($stub, $return);
    }

    public function testReconnectBad()
    {
        $stub = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(FALSE));

        $this->setExpectedException("Bridge_Exception_ApiConnectorNotConfigured");

        $stub->reconnect();
    }

    public function testDisconnect()
    {
        $stub = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(TRUE));

        $this->auth->expects($this->once())
            ->method('disconnect');

        $return = $stub->disconnect();

        $this->assertEquals($stub, $return);
    }

    public function testDisconnectBad()
    {
        $stub = $this->getMock('Bridge_Api_Abstract', array("is_configured", "initialize_transport", "set_auth_params", "set_transport_authentication_params"), array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->expects($this->once())
            ->method('is_configured')
            ->will($this->returnValue(FALSE));

        $this->setExpectedException("Bridge_Exception_ApiConnectorNotConfigured");

        $stub->disconnect();
    }

    public function testIs_connected()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Abstract', array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $this->auth->expects($this->once())
            ->method('is_connected')
            ->will($this->returnValue(TRUE));

        $return = $stub->is_connected();

        $this->assertEquals(TRUE, $return);
    }

    public function testGet_auth_url()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Abstract', array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $this->auth->expects($this->once())
            ->method('get_auth_url')
            ->with($this->isType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY))
            ->will($this->returnValue("une url"));

        $return = $stub->get_auth_url();

        $this->assertEquals("une url", $return);
    }

    public function testSet_locale()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Abstract', array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $stub->set_locale("fr");

        $this->assertEquals("fr", $stub->get_locale());
    }

    public function testIs_valid_object_id()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Abstract', array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));

        $this->assertTrue($stub->is_valid_object_id("abc"));
        $this->assertTrue($stub->is_valid_object_id(123));
        $this->assertTrue($stub->is_valid_object_id(12.25));
        $this->assertFalse($stub->is_valid_object_id(array()));
        $this->assertFalse($stub->is_valid_object_id(true));
    }

    public function testHandle_exception()
    {
        $stub = $this->getMockForAbstractClass('Bridge_Api_Abstract', array(registry::get_instance(), $this->auth, "Mock_Bridge_Api_Abstract"));
        $e = new Exception("hihi");
        $void = $stub->handle_exception($e);
        $this->assertNull($void);
    }
}
