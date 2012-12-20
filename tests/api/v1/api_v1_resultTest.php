<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../../vendor/alchemy/oauth2php/lib/OAuth2.php';

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
        $this->api = $this->getMock("API_V1_adapter", array("get_version"), array(), "", false);
        $this->api->expects($this->any())->method("get_version")->will($this->returnValue("my_super_version1.0"));
    }

    public function testFormat()
    {
        $server = array(
            "HTTP_ACCEPT"     => "application/json"
            , 'REQUEST_METHOD'  => 'GET'
            , 'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename'
            , "REQUEST_URI"     => "my/base/path/my/request/uri"
            , 'PHP_SELF'        => 'my/base/path'
        );
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);

        $api_result = new API_V1_result($request, $this->api);
        $return = $api_result->format();
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $return);
        $response = json_decode($return);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $response);
        $this->assertObjectHasAttribute("meta", $response);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $response->meta);
        $this->assertObjectHasAttribute("response", $response);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $response->response);
        $this->assertEquals(0, sizeof(get_object_vars($response->response)));
        $this->assertEquals(0, sizeof(get_class_methods($response->response)));
        $this->checkResponseFieldMeta($response, "api_version", "my_super_version1.0", PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->checkResponseFieldMeta($response, "request", "GET my/base/path/my/request/uri", PHPUnit_Framework_Constraint_IsType::TYPE_STRING);

        $date = new \DateTime();
        $now = $date->format('U');

        $date_query = \DateTime::createFromFormat(DATE_ATOM, $response->meta->response_time);
        $now_query = $date_query->format('U');

        $this->assertLessThan(1, $now_query - $now);

        $this->assertDateAtom($response->meta->response_time);
        $date = new DateTime();
        $now_U = $date->format('U');
        $date_resp = DateTime::createFromFormat(DATE_ATOM, $response->meta->response_time);
        $resp_U = $date_resp->format('U');

        $this->assertLessThan(3, abs($resp_U - $now_U), 'No more than 3sec between now and the query');

        $this->checkResponseFieldMeta($response, "http_code", 200, PHPUnit_Framework_Constraint_IsType::TYPE_INT);
        $this->checkResponseFieldMeta($response, "charset", "UTF-8", PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->assertObjectHasAttribute("error_message", $response->meta);
        $this->assertNull($response->meta->error_message);
        $this->assertObjectHasAttribute("error_details", $response->meta);
        $this->assertNull($response->meta->error_details);

        $server = array(
            "HTTP_ACCEPT"     => "application/yaml"
            , 'REQUEST_METHOD'  => 'GET'
            , 'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename'
            , "REQUEST_URI"     => "my/base/path/my/request/uri"
            , 'PHP_SELF'        => 'my/base/path'
        );
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);

        $api_result = new API_V1_result($request, $this->api);
        $return = $api_result->format();
        $sfYaml = new Symfony\Component\Yaml\Parser();
        $response = $sfYaml->parse($return);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response);
        $this->assertArrayHasKey("meta", $response);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response["meta"]);
        $this->assertArrayHasKey("response", $response);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response["response"]);
        $this->assertEquals(0, count($response["response"]));
        $this->assertArrayHasKey("api_version", $response["meta"]);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["api_version"]);
        $this->assertEquals("my_super_version1.0", $response["meta"]["api_version"]);
        $this->assertArrayHasKey("request", $response["meta"]);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["request"]);
        $this->assertEquals("GET my/base/path/my/request/uri", $response["meta"]["request"]);
        $this->assertArrayHasKey("response_time", $response["meta"]);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["response_time"]);

        $this->assertDateAtom($response["meta"]["response_time"]);
        $date_obj1 = DateTime::createFromFormat(DATE_ATOM, $response["meta"]["response_time"]);
        $date_obj2 = new DateTime();
        $this->assertLessThan(3, abs($date_obj1->format('U') - $date_obj2->format('U')), 'No more than 3sec between now and the query');


        $this->assertArrayHasKey("http_code", $response["meta"]);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $response["meta"]["http_code"]);
        $this->assertEquals(200, $response["meta"]["http_code"]);
        $this->assertArrayHasKey("error_message", $response["meta"]);
        $this->assertNull($response["meta"]["error_message"]);
        $this->assertArrayHasKey("error_details", $response["meta"]);
        $this->assertNull($response["meta"]["error_details"]);
        $this->assertArrayHasKey("charset", $response["meta"]);
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["charset"]);
        $this->assertEquals("UTF-8", $response["meta"]["charset"]);


        $server = array(
            "HTTP_ACCEPT"     => "application/yaml"
            , 'REQUEST_METHOD'  => 'GET'
            , 'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename'
            , "REQUEST_URI"     => "my/base/path/my/request/uri"
            , 'PHP_SELF'        => 'my/base/path'
        );
        $request = new Request(array("callback" => "my_callback_function"), array(), array(), array(), array(), $server);

        $api_result = new API_V1_result($request, $this->api);
        $return = $api_result->format();
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $return);
        $this->assertRegexp("/my_callback_function\(\{.+\}\)/", $return);
    }

    /**
     * @depends testFormat
     */
    public function testSet_datas()
    {
        $server = array(
            "HTTP_ACCEPT"     => "application/json"
            , 'REQUEST_METHOD'  => 'GET'
            , 'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename'
            , "REQUEST_URI"     => "my/base/path/my/request/uri"
            , 'PHP_SELF'        => 'my/base/path'
        );
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);

        $api_result = new API_V1_result($request, $this->api);
        $api_result->set_datas(array("pirouette" => "cacahuete", "black"     => true, "bob"       => array("bob")));
        $response = json_decode($api_result->format());
        $this->checkResponseFieldResponse($response, "pirouette", "cacahuete", PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->checkResponseFieldResponse($response, "black", true, PHPUnit_Framework_Constraint_IsType::TYPE_BOOL);
        $this->checkResponseFieldResponse($response, "bob", array("bob"), PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY);
    }

    public function testGet_datas()
    {
        $server = array(
            "HTTP_ACCEPT"     => "application/json"
            , 'REQUEST_METHOD'  => 'GET'
            , 'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename'
            , "REQUEST_URI"     => "my/base/path/my/request/uri"
            , 'PHP_SELF'        => 'my/base/path'
        );
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);

        $data = array("pirouette" => "cacahuete", "black"     => true, "bob"       => array("bob"));
        $api_result = new API_V1_result($request, $this->api);
        $api_result->set_datas($data);

        $this->assertEquals($data, $api_result->get_datas());
    }

    public function testGet_Emptydatas()
    {
        $server = array(
            "HTTP_ACCEPT"     => "application/json"
            , 'REQUEST_METHOD'  => 'GET'
            , 'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename'
            , "REQUEST_URI"     => "my/base/path/my/request/uri"
            , 'PHP_SELF'        => 'my/base/path'
        );
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);

        $data = array();
        $api_result = new API_V1_result($request, $this->api);
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

    public function testGet_content_type()
    {
        $server = array("HTTP_ACCEPT" => "application/json");
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);
        $api_result = new API_V1_result($request, $this->api);
        $this->assertEquals("application/json", $api_result->get_content_type());

        $server = array("HTTP_ACCEPT" => "application/yaml");
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);
        $api_result = new API_V1_result($request, $this->api);
        $this->assertEquals('application/yaml', $api_result->get_content_type());

        $server = array("HTTP_ACCEPT" => "text/yaml");
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);
        $api_result = new API_V1_result($request, $this->api);
        $this->assertEquals('application/yaml', $api_result->get_content_type());

        $server = array("HTTP_ACCEPT" => "");
        $request = new Request(array("callback" => "hello"), array(), array(), array(), array(), $server);
        $api_result = new API_V1_result($request, $this->api);
        $this->assertEquals('text/javascript', $api_result->get_content_type());

        $server = array("HTTP_ACCEPT" => "unknow");
        $request = new Request(array("callback" => ""), array(), array(), array(), array(), $server);
        $api_result = new API_V1_result($request, $this->api);
        $this->assertEquals("application/json", $api_result->get_content_type());
    }

    /**
     * @depends testFormat
     */
    public function testSet_error_message()
    {
        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_BAD_REQUEST, 'detaillage');
        $this->assertErrorMessage($api_result, 400, API_V1_result::ERROR_BAD_REQUEST, API_V1_exception_badrequest::get_details(), 'detaillage');

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_UNAUTHORIZED, 'detaillage');
        $this->assertErrorMessage($api_result, 401, API_V1_result::ERROR_UNAUTHORIZED, API_V1_exception_unauthorized::get_details(), 'detaillage');

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_FORBIDDEN, 'detaillage');
        $this->assertErrorMessage($api_result, 403, API_V1_result::ERROR_FORBIDDEN, API_V1_exception_forbidden::get_details(), 'detaillage');

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_NOTFOUND, 'detaillage');
        $this->assertErrorMessage($api_result, 404, API_V1_result::ERROR_NOTFOUND, API_V1_exception_notfound::get_details(), 'detaillage');

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_METHODNOTALLOWED, 'detaillage');
        $this->assertErrorMessage($api_result, 405, API_V1_result::ERROR_METHODNOTALLOWED, API_V1_exception_methodnotallowed::get_details(), 'detaillage');

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_message(API_V1_result::ERROR_INTERNALSERVERERROR, 'detaillage');
        $this->assertErrorMessage($api_result, 500, API_V1_result::ERROR_INTERNALSERVERERROR, API_V1_exception_internalservererror::get_details(), 'detaillage');

        $api_result = new API_V1_result(new Request(), $this->api);
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

    /**
     * @depends testFormat
     */
    public function testSet_error_code()
    {
        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(400);
        $this->assertErrorMessage($api_result, 400, API_V1_result::ERROR_BAD_REQUEST, API_V1_exception_badrequest::get_details(), null);

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(401);
        $this->assertErrorMessage($api_result, 401, API_V1_result::ERROR_UNAUTHORIZED, API_V1_exception_unauthorized::get_details(), null);

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(403);
        $this->assertErrorMessage($api_result, 403, API_V1_result::ERROR_FORBIDDEN, API_V1_exception_forbidden::get_details(), null);

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(404);
        $this->assertErrorMessage($api_result, 404, API_V1_result::ERROR_NOTFOUND, API_V1_exception_notfound::get_details(), null);

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(405);
        $this->assertErrorMessage($api_result, 405, API_V1_result::ERROR_METHODNOTALLOWED, API_V1_exception_methodnotallowed::get_details(), null);

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(500);
        $this->assertErrorMessage($api_result, 500, API_V1_result::ERROR_INTERNALSERVERERROR, API_V1_exception_internalservererror::get_details(), null);
    }

    public function testGet_http_code()
    {
        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(400);
        $this->assertEquals(400, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(401);
        $this->assertEquals(401, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(403);
        $this->assertEquals(403, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(404);
        $this->assertEquals(404, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(405);
        $this->assertEquals(405, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_error_code(500);
        $this->assertEquals(500, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(array("callback" => "my_callback")), $this->api);
        $api_result->set_error_code(400);
        $this->assertEquals(200, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(array("callback" => "my_callback")), $this->api);
        $api_result->set_error_code(500);
        $this->assertEquals(500, $api_result->get_http_code());
    }

    public function testSet_http_code()
    {
        $api_result = new API_V1_result(new Request(), $this->api);
        $api_result->set_http_code(500);
        $this->assertEquals(500, $api_result->get_http_code());

        $api_result->set_http_code(400);
        $this->assertEquals(400, $api_result->get_http_code());
        $api_result->set_http_code(401);
        $this->assertEquals(401, $api_result->get_http_code());
        $api_result->set_http_code(403);
        $this->assertEquals(403, $api_result->get_http_code());

        $api_result = new API_V1_result(new Request(array("callback" => "my_callback")), $this->api);
        $api_result->set_http_code(500);
        $this->assertEquals(500, $api_result->get_http_code());

        $api_result->set_http_code(400);
        $this->assertEquals(200, $api_result->get_http_code());

        $api_result->set_http_code(401);
        $this->assertEquals(200, $api_result->get_http_code());

        $api_result->set_http_code(403);
        $this->assertEquals(200, $api_result->get_http_code());

        $api_result->set_http_code(404);
        $this->assertEquals(200, $api_result->get_http_code());

        $api_result->set_http_code(405);
        $this->assertEquals(200, $api_result->get_http_code());
    }
}
