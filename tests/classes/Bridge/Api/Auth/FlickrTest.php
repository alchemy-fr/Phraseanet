<?php

require_once __DIR__ . '/../../Bridge_datas.inc';

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class Bridge_Api_Auth_FlickrTest extends \PhraseanetTestCase
{
    /**
     * @var Bridge_Api_Auth_Flickr
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Bridge_Api_Auth_Flickr();
    }

    public function testParse_request_token()
    {
        $this->assertNull($this->object->parse_request_token());
        $_GET["frob"] = "123";
        $this->assertEquals("123", $this->object->parse_request_token());
        unset($_GET["frob"]);
        $this->assertNull($this->object->parse_request_token());
    }

    public function testConnect()
    {
        $api = $this->createMock("Phlickr_Api");
        //mock api method
        $api->expects($this->once())
            ->method("setAuthTokenFromFrob")
            ->will($this->returnValue("un_token"));

        $api->expects($this->once())
            ->method("setAuthToken")
            ->with($this->equalTo("un_token"));

        $api->expects($this->once())
            ->method("isAuthValid")
            ->will($this->returnValue(true));

        $stub = $this->getMockBuilder("Bridge_Api_Auth_Flickr")->onlyMethods(["get_api"])->getMock();

        $stub
            ->method("get_api")
            ->will($this->returnValue($api));

        $return = $stub->connect("123");

        $this->assertIsArray($return);
        $this->assertArrayHasKey("auth_token", $return);
        $this->assertEquals("un_token", $return["auth_token"]);
    }

    public function testBadConnect()
    {
        $api = $this->createMock("Phlickr_Api");
        //mock api method
        $api->expects($this->once())
            ->method("setAuthTokenFromFrob")
            ->will($this->returnValue("un_token"));

        $api->expects($this->once())
            ->method("isAuthValid")
            ->will($this->returnValue(false));

        $stub = $this->getMockBuilder("Bridge_Api_Auth_Flickr")->onlyMethods(["get_api"])->getMock();

        $stub
            ->method("get_api")
            ->will($this->returnValue($api));

        $this->expectException("Bridge_Exception_ApiConnectorAccessTokenFailed");

        $stub->connect("123");
    }

    public function testReconnect()
    {
        $this->assertEquals($this->object, $this->object->reconnect());
    }

    public function testDisconnect()
    {
        $setting = $this->getMockBuilder("Bridge_AccountSettings")->disableOriginalConstructor()->onlyMethods(["set"])->getMock();

        $setting->expects($this->once())
            ->method("set")
            ->with($this->equalTo("auth_token"), $this->isNull());

        $this->object->set_settings($setting);

        $return = $this->object->disconnect();

        $this->assertEquals($this->object, $return);
    }

    public function testIs_connected()
    {
        $setting = $this->getMockBuilder("Bridge_AccountSettings")->disableOriginalConstructor()->onlyMethods(["get"])->getMock();

        $setting
            ->method("get")
            ->with($this->equalTo("auth_token"))
            ->will($this->onConsecutiveCalls("123456", 123456, null));

        $this->object->set_settings($setting);

        $this->assertTrue($this->object->is_connected());
        $this->assertTrue($this->object->is_connected());
        $this->assertFalse($this->object->is_connected());
    }

    public function testGet_auth_signatures()
    {
        $setting = $this->getMockBuilder("Bridge_AccountSettings")->disableOriginalConstructor()->onlyMethods(["get"])->getMock();

        $setting->expects($this->once())
            ->method("get")
            ->with($this->equalTo("auth_token"))
            ->will($this->returnValue("123"));

        $this->object->set_settings($setting);

        $return = $this->object->get_auth_signatures();

        $this->assertIsArray($return);
        $this->assertArrayHasKey("auth_token", $return);
        $this->assertEquals("123", $return["auth_token"]);
    }

    public function testSet_parameters()
    {
        $parameters = [
            "caca"      => "boudain"
            , "sirop"     => "fraise"
            , "choco"     => "banane"
            , "pirouette" => "cacahuete"
        ];

        $return = $this->object->set_parameters($parameters);

        $this->assertEquals(0, sizeof(get_object_vars($this->object)));
        $this->assertEquals($return, $this->object);
    }

    public function testGet_auth_url()
    {
        $api = $this->createMock("Phlickr_Api");
        //mock api method
        $api
            ->method("requestFrob")
            ->will($this->returnValue("un_token"));

        $api
            ->method("buildAuthUrl")
            ->with($this->equalTo("write"), $this->equalTo("un_token"))
            ->will($this->returnValue("une_super_url"));

        $stub = $this->getMockBuilder("Bridge_Api_Auth_Flickr")->onlyMethods(["get_api"])->getMock();

        $stub
            ->method("get_api")
            ->will($this->returnValue($api));

        $params = ["permissions" => "write"];

        $stub->set_parameters($params);

        $this->assertEquals("une_super_url", $stub->get_auth_url());
        $this->assertEquals("une_super_url", $stub->get_auth_url($params));
    }
}
