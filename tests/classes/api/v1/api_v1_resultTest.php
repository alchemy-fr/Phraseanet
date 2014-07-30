<?php

require_once __DIR__ . '/../../../../vendor/alchemy/oauth2php/lib/OAuth2.php';

use Symfony\Component\HttpFoundation\Request;

class API_V1_resultTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var API_V1_result
     */
    protected $api;

    public function setUp()
    {
        parent::setUp();

        self::$DI['app']->register(new \API_V1_Timer());

        $conf = self::$DI['app']['phraseanet.configuration']->getConfig();
        $conf['main']['api-timers'] = true;
        self::$DI['app']['phraseanet.configuration']->setConfig($conf);

        $this->api = $this->getMock("API_V1_adapter", array("get_version"), array(), "", false);
        $this->api->expects($this->any())->method("get_version")->will($this->returnValue("my_super_version1.0"));
    }

    protected function assertIsTimer($timer)
    {
        $this->assertObjectHasAttribute('name', $timer);
        $this->assertObjectHasAttribute('memory', $timer);
        $this->assertObjectHasAttribute('time', $timer);
    }

    public function testSet_datas()
    {
        $request = new Request();

        $api_result = new API_V1_result(self::$DI['app'], $request, $this->api);
        $api_result->set_datas(array("pirouette" => "cacahuete", "black"     => true, "bob"       => array("bob")));
        $response = json_decode($api_result->format());
        $this->checkResponseFieldResponse($response, "pirouette", "cacahuete", PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->checkResponseFieldResponse($response, "black", true, PHPUnit_Framework_Constraint_IsType::TYPE_BOOL);
        $this->checkResponseFieldResponse($response, "bob", array("bob"), PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY);
    }

    public function testGet_datas()
    {
        $request = new Request();

        $data = array("pirouette" => "cacahuete", "black"     => true, "bob"       => array("bob"));
        $api_result = new API_V1_result(self::$DI['app'], $request, $this->api);
        $api_result->set_datas($data);

        $this->assertEquals($data, $api_result->get_datas());
    }

    public function testGet_Emptydatas()
    {
        $request = new Request();

        $data = array();
        $api_result = new API_V1_result(self::$DI['app'], $request, $this->api);
        $api_result->set_datas($data);

        $this->assertEquals($data, $api_result->get_datas());
    }

    protected function checkResponseFieldMeta(stdClass $response, $field, $expected_value, $type)
    {
        $this->assertObjectHasAttribute($field, $response->meta);
        $this->assertInternalType($type, $response->meta->$field);
        $this->assertEquals($expected_value, $response->meta->$field);
    }

    protected function checkResponseFieldResponse(stdClass $response, $field, $expected_value, $type)
    {
        $this->assertObjectHasAttribute($field, $response->response);
        $this->assertInternalType($type, $response->response->$field);
        $this->assertEquals($expected_value, $response->response->$field);
    }

    public function testSet_error_message()
    {
        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, 'detaillage');
        $this->assertErrorMessage($api_result, 400, API_V1_result::ERROR_BAD_REQUEST, API_V1_exception_badrequest::get_details(), 'detaillage');

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_UNAUTHORIZED, 'detaillage');
        $this->assertErrorMessage($api_result, 401, API_V1_result::ERROR_UNAUTHORIZED, API_V1_exception_unauthorized::get_details(), 'detaillage');

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_FORBIDDEN, 'detaillage');
        $this->assertErrorMessage($api_result, 403, API_V1_result::ERROR_FORBIDDEN, API_V1_exception_forbidden::get_details(), 'detaillage');

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_NOTFOUND, 'detaillage');
        $this->assertErrorMessage($api_result, 404, API_V1_result::ERROR_NOTFOUND, API_V1_exception_notfound::get_details(), 'detaillage');

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_METHODNOTALLOWED, 'detaillage');
        $this->assertErrorMessage($api_result, 405, API_V1_result::ERROR_METHODNOTALLOWED, API_V1_exception_methodnotallowed::get_details(), 'detaillage');

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_INTERNALSERVERERROR, 'detaillage');
        $this->assertErrorMessage($api_result, 500, API_V1_result::ERROR_INTERNALSERVERERROR, API_V1_exception_internalservererror::get_details(), 'detaillage');

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_message(OAUTH2_ERROR_INVALID_REQUEST, 'detaillage');
        $this->assertErrorMessage($api_result, 200, OAUTH2_ERROR_INVALID_REQUEST, NULL, 'detaillage');
    }

    protected function assertErrorMessage(API_V1_result $api_result, $code, $type, $message, $detail)
    {
        $response = json_decode($api_result->format());
        $this->checkResponseFieldMeta($response, 'http_code', $code, PHPUnit_Framework_Constraint_IsType::TYPE_INT);

        if (is_null($type)) {
            $this->assertObjectHasAttribute('error_type', $response->meta);
            $this->assertNull($response->meta->error_type);
        } else {
            $this->checkResponseFieldMeta($response, 'error_type', $type, PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        }

        if (is_null($message)) {
            $this->assertObjectHasAttribute('error_message', $response->meta);
            $this->assertNull($response->meta->error_message);
        } else {
            $this->checkResponseFieldMeta($response, 'error_message', $message, PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        }

        if (is_null($detail)) {
            $this->assertObjectHasAttribute('error_details', $response->meta);
            $this->assertNull($response->meta->error_details);
        } else {
            $this->checkResponseFieldMeta($response, 'error_details', $detail, PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        }
    }

    public function testSet_error_code()
    {
        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(400);
        $this->assertErrorMessage($api_result, 400, API_V1_result::ERROR_BAD_REQUEST, API_V1_exception_badrequest::get_details(), null);

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(401);
        $this->assertErrorMessage($api_result, 401, API_V1_result::ERROR_UNAUTHORIZED, API_V1_exception_unauthorized::get_details(), null);

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(403);
        $this->assertErrorMessage($api_result, 403, API_V1_result::ERROR_FORBIDDEN, API_V1_exception_forbidden::get_details(), null);

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(404);
        $this->assertErrorMessage($api_result, 404, API_V1_result::ERROR_NOTFOUND, API_V1_exception_notfound::get_details(), null);

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(405);
        $this->assertErrorMessage($api_result, 405, API_V1_result::ERROR_METHODNOTALLOWED, API_V1_exception_methodnotallowed::get_details(), null);

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(500);
        $this->assertErrorMessage($api_result, 500, API_V1_result::ERROR_INTERNALSERVERERROR, API_V1_exception_internalservererror::get_details(), null);
    }

    public function testGet_http_code()
    {
        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(400);
        $this->assertEquals(400, $api_result->get_http_code());

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(401);
        $this->assertEquals(401, $api_result->get_http_code());

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(403);
        $this->assertEquals(403, $api_result->get_http_code());

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(404);
        $this->assertEquals(404, $api_result->get_http_code());

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(405);
        $this->assertEquals(405, $api_result->get_http_code());

        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_error_code(500);
        $this->assertEquals(500, $api_result->get_http_code());
    }

    public function testSet_http_code()
    {
        $api_result = new API_V1_result(self::$DI['app'], new Request(), $this->api);
        $api_result->set_http_code(500);
        $this->assertEquals(500, $api_result->get_http_code());

        $api_result->set_http_code(400);
        $this->assertEquals(400, $api_result->get_http_code());
        $api_result->set_http_code(401);
        $this->assertEquals(401, $api_result->get_http_code());
        $api_result->set_http_code(403);
        $this->assertEquals(403, $api_result->get_http_code());
    }
}
