<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../Bridge_datas.inc';

class Bridge_Api_Auth_OAuth2Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_Auth_OAuth2
     */
    protected $object;
    protected $parameters;
    protected $mockSettings;

    public function setUp()
    {
        $this->object = new Bridge_Api_Auth_OAuth2();

        $this->parameters = array(
            'client_id'      => "client_id"
            , 'client_secret'  => "client_secret"
            , 'redirect_uri'   => "redirect_uri"
            , 'scope'          => 'super_scope'
            , 'response_type'  => 'code'
            , 'token_endpoint' => "one_token_endpoint"
            , 'auth_endpoint'  => "one_auth_endpoint"
        );

        $this->mockSettings = $this->getMock("Bridge_AccountSettings", array("get", "set"), array(), "", false);
    }

    public function testParse_request_token()
    {
        $this->object->set_parameters($this->parameters);
        $_GET = array("code" => "12345");
        $token = $this->object->parse_request_token();
        $this->assertEquals("12345", $token);
        $this->parameters["response_type"] = "blabla";
        $this->object->set_parameters($this->parameters);
        $this->assertNull($this->object->parse_request_token());
    }

    public function testConnect()
    {
        $this->setExpectedException("Bridge_Exception_ApiConnectorAccessTokenFailed");

        $this->object->connect("123");
    }

    public function testReconnect()
    {
        $this->mockSettings->expects($this->once())
            ->method("get")
            ->with($this->equalTo("refresh_token"))
            ->will($this->returnValue("123"));

        $this->object->set_settings($this->mockSettings);

        $this->setExpectedException("Bridge_Exception_ApiConnectorAccessTokenFailed");

        $this->object->reconnect();
    }

    public function testDisconnect()
    {
        $this->mockSettings->expects($this->once())
            ->method("set")
            ->with($this->equalTo("auth_token"), $this->isNull());

        $this->object->set_settings($this->mockSettings);

        $return = $this->object->disconnect();

        $this->assertEquals($this->object, $return);
    }

    public function testIs_connected()
    {
        $this->mockSettings->expects($this->any())
            ->method("get")
            ->with($this->equalTo("auth_token"))
            ->will($this->onConsecutiveCalls("123456", 123456, null));

        $this->object->set_settings($this->mockSettings);

        $this->assertTrue($this->object->is_connected());
        $this->assertTrue($this->object->is_connected());
        $this->assertFalse($this->object->is_connected());
    }

    public function testGet_auth_signatures()
    {
        $this->mockSettings->expects($this->once())
            ->method("get")
            ->with($this->equalTo("auth_token"))
            ->will($this->returnValue("123"));

        $this->object->set_settings($this->mockSettings);

        $return = $this->object->get_auth_signatures();

        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $return);
        $this->assertArrayHasKey("auth_token", $return);
        $this->assertEquals("123", $return["auth_token"]);
    }

    public function testSet_parameters()
    {
        $parameters = array(
            "client_id"     => "cid"
            , "allo"          => "hello"
            , "yo"            => "coucou"
            , "response_type" => "hihi"
        );

        $return = $this->object->set_parameters($parameters);

        $this->assertEquals(0, sizeof(get_object_vars($this->object)));
        $this->assertEquals($return, $this->object);
    }

    public function testGet_auth_url()
    {
        $this->object->set_parameters($this->parameters);
        $expected_url = "one_auth_endpoint?response_type=code&client_id=client_id&redirect_uri=redirect_uri&scope=super_scope";
        $this->assertEquals($expected_url, $this->object->get_auth_url());

        $more_params = array("test" => "test");
        $this->assertEquals($expected_url . "&test=test", $this->object->get_auth_url($more_params));

        $more_params = array("response_type" => "test");
        $this->assertNotEquals($expected_url, $this->object->get_auth_url($more_params));
    }
}
