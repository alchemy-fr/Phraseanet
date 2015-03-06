<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Api\V1;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;

class ResultTest extends \PhraseanetTestCase
{
    public function testFormatJson()
    {
        $server = [
            'HTTP_ACCEPT'     => 'application/json',
            'REQUEST_METHOD'  => 'GET',
            'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename',
            'REQUEST_URI'     => 'my/base/path/my/request/uri',
            'PHP_SELF'        => 'my/base/path',
        ];
        $request = new Request(["callback" => ""], [], [], [], [], $server);

        $apiResult = new Result($request);
        $return = $apiResult->createResponse()->getContent();
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $return);
        $response = json_decode($return);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $response);
        $this->assertObjectHasAttribute("meta", $response);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $response->meta);
        $this->assertObjectHasAttribute("response", $response);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_OBJECT, $response->response);
        $this->assertEquals(0, sizeof(get_object_vars($response->response)));
        $this->assertEquals(0, sizeof(get_class_methods($response->response)));
        $this->checkResponseFieldMeta($response, "api_version", V1::VERSION, \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->checkResponseFieldMeta($response, "request", "GET my/base/path/my/request/uri", \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);

        $date = new \DateTime();
        $now = $date->format('U');

        $dateQuery = \DateTime::createFromFormat(DATE_ATOM, $response->meta->response_time);
        $nowQuery = $dateQuery->format('U');

        $this->assertLessThan(1, $nowQuery - $now);

        $this->assertDateAtom($response->meta->response_time);
        $date = new \DateTime();
        $nowU = $date->format('U');
        $dateResp = \DateTime::createFromFormat(DATE_ATOM, $response->meta->response_time);
        $respU = $dateResp->format('U');

        $this->assertLessThan(3, abs($respU - $nowU), 'No more than 3sec between now and the query');

        $this->checkResponseFieldMeta($response, "http_code", 200, \PHPUnit_Framework_Constraint_IsType::TYPE_INT);
        $this->checkResponseFieldMeta($response, "charset", "UTF-8", \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->assertObjectHasAttribute("error_message", $response->meta);
        $this->assertNull($response->meta->error_message);
        $this->assertObjectHasAttribute("error_details", $response->meta);
        $this->assertNull($response->meta->error_details);
    }

    public function testFormatYaml()
    {
        $server = [
            'HTTP_ACCEPT'     => 'application/yaml',
            'REQUEST_METHOD'  => 'GET',
            'SCRIPT_FILENAME' => 'my/base/path/my/request/uri/filename',
            'REQUEST_URI'     => 'my/base/path/my/request/uri',
            'PHP_SELF'        => 'my/base/path',
        ];
        $request = new Request(["callback" => ""], [], [], [], [], $server);

        $apiResult = new Result($request);
        $response = (new Parser())->parse($apiResult->createResponse()->getContent());

        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response);
        $this->assertArrayHasKey("meta", $response);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response["meta"]);
        $this->assertArrayHasKey("response", $response);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $response["response"]);
        $this->assertEquals(0, count($response["response"]));
        $this->assertArrayHasKey("api_version", $response["meta"]);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["api_version"]);
        $this->assertEquals(V1::VERSION, $response["meta"]["api_version"]);
        $this->assertArrayHasKey("request", $response["meta"]);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["request"]);
        $this->assertEquals("GET my/base/path/my/request/uri", $response["meta"]["request"]);
        $this->assertArrayHasKey("response_time", $response["meta"]);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["response_time"]);

        $this->assertDateAtom($response["meta"]["response_time"]);
        $dateObj1 = \DateTime::createFromFormat(DATE_ATOM, $response["meta"]["response_time"]);
        $dateObj2 = new \DateTime();
        $this->assertLessThan(3, abs($dateObj1->format('U') - $dateObj2->format('U')), 'No more than 3sec between now and the query');

        $this->assertArrayHasKey("http_code", $response["meta"]);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $response["meta"]["http_code"]);
        $this->assertEquals(200, $response["meta"]["http_code"]);
        $this->assertArrayHasKey("error_message", $response["meta"]);
        $this->assertNull($response["meta"]["error_message"]);
        $this->assertArrayHasKey("error_details", $response["meta"]);
        $this->assertNull($response["meta"]["error_details"]);
        $this->assertArrayHasKey("charset", $response["meta"]);
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $response["meta"]["charset"]);
        $this->assertEquals("UTF-8", $response["meta"]["charset"]);
    }

    public function testFormatJsonP()
    {
        $request = new Request(["callback" => "my_callback_function"], [], [], [], [], ["HTTP_ACCEPT"     => "application/yaml"]);
        $apiResult = new Result($request);
        $return = $apiResult->createResponse()->getContent();
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $return);
        $this->assertRegexp("/my_callback_function\\(\\{.+\\}\\);/", $return);
        $response = json_decode(substr($return, 25, -2), true);
        $this->assertSame([], $response['response']);
    }

    public function testData()
    {
        $apiResult = new Result(new Request(), ["pirouette" => "cacahuete", "black" => true, "bob" => ["bob"]]);
        $response = json_decode($apiResult->createResponse()->getContent());
        $this->checkResponseFieldResponse($response, "pirouette", "cacahuete", \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        $this->checkResponseFieldResponse($response, "black", true, \PHPUnit_Framework_Constraint_IsType::TYPE_BOOL);
        $this->checkResponseFieldResponse($response, "bob", ["bob"], \PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY);
    }

    public function testEmptyData()
    {
        $apiResult = new Result(new Request(), []);
        $content = json_decode($apiResult->createResponse()->getContent(), true);

        $this->assertSame([], $content['response']);
    }

    public function testContentType()
    {
        $server = ["HTTP_ACCEPT" => "application/json"];
        $request = new Request(["callback" => ""], [], [], [], [], $server);
        $apiResult = new Result($request);
        $this->assertEquals("application/json", $apiResult->createResponse()->headers->get('content-type'));

        $server = ["HTTP_ACCEPT" => "application/yaml"];
        $request = new Request(["callback" => ""], [], [], [], [], $server);
        $apiResult = new Result($request);
        $this->assertEquals('application/yaml', $apiResult->createResponse()->headers->get('content-type'));

        $server = ["HTTP_ACCEPT" => "text/yaml"];
        $request = new Request(["callback" => ""], [], [], [], [], $server);
        $apiResult = new Result($request);
        $this->assertEquals('application/yaml', $apiResult->createResponse()->headers->get('content-type'));

        $server = ["HTTP_ACCEPT" => ""];
        $request = new Request(["callback" => "hello"], [], [], [], [], $server);
        $apiResult = new Result($request);
        $this->assertEquals('text/javascript', $apiResult->createResponse()->headers->get('content-type'));

        $server = ["HTTP_ACCEPT" => "unknow"];
        $request = new Request(["callback" => ""], [], [], [], [], $server);
        $apiResult = new Result($request);
        $this->assertEquals("application/json", $apiResult->createResponse()->headers->get('content-type'));
    }

    public function testConstructor()
    {
        $apiResult = new Result(new Request(), [], 400, 'type', Result::ERROR_BAD_REQUEST, 'details');
        $this->assertErrorMessage($apiResult, 400, 'type', Result::ERROR_BAD_REQUEST, 'details');

        $apiResult = new Result(new Request(), [], 401, 'type', Result::ERROR_UNAUTHORIZED, 'details');
        $this->assertErrorMessage($apiResult, 401, 'type', Result::ERROR_UNAUTHORIZED, 'details');

        $apiResult = new Result(new Request(), [], 403, 'type', Result::ERROR_FORBIDDEN, 'details');
        $this->assertErrorMessage($apiResult, 403, 'type', Result::ERROR_FORBIDDEN, 'details');

        $apiResult = new Result(new Request(), [], 404, 'type', Result::ERROR_NOTFOUND, 'details');
        $this->assertErrorMessage($apiResult, 404, 'type', Result::ERROR_NOTFOUND, 'details');

        $apiResult = new Result(new Request(), [], 405, 'type', Result::ERROR_METHODNOTALLOWED, 'details');
        $this->assertErrorMessage($apiResult, 405, 'type', Result::ERROR_METHODNOTALLOWED, 'details');

        $apiResult = new Result(new Request(), [], 500, 'type', Result::ERROR_INTERNALSERVERERROR, 'details');
        $this->assertErrorMessage($apiResult, 500, 'type', Result::ERROR_INTERNALSERVERERROR, 'details');
    }

    public function testCreateError()
    {
        $apiResult = Result::createError(new Request(), 400, 'detaillage');
        $this->assertErrorMessage($apiResult, 400, Result::ERROR_BAD_REQUEST, 'Parameter is invalid or missing', 'detaillage');

        $apiResult = Result::createError(new Request(), 401, 'detaillage');
        $this->assertErrorMessage($apiResult, 401, Result::ERROR_UNAUTHORIZED, 'The OAuth token was provided but was invalid.', 'detaillage');

        $apiResult = Result::createError(new Request(), 403, 'detaillage');
        $this->assertErrorMessage($apiResult, 403, Result::ERROR_FORBIDDEN, 'Access to the requested resource is forbidden', 'detaillage');

        $apiResult = Result::createError(new Request(), 404, 'detaillage');
        $this->assertErrorMessage($apiResult, 404, Result::ERROR_NOTFOUND, 'Requested resource is not found', 'detaillage');

        $apiResult = Result::createError(new Request(), 405, 'detaillage');
        $this->assertErrorMessage($apiResult, 405, Result::ERROR_METHODNOTALLOWED, 'Attempting to use POST with a GET-only endpoint, or vice-versa', 'detaillage');

        $apiResult = Result::createError(new Request(), 500, 'detaillage');
        $this->assertErrorMessage($apiResult, 500, Result::ERROR_INTERNALSERVERERROR, 'Internal Server Error', 'detaillage');
    }

    private function checkResponseFieldMeta(\stdClass $response, $field, $expectedValue, $type)
    {
        $this->assertObjectHasAttribute($field, $response->meta);
        $this->assertInternalType($type, $response->meta->$field);
        $this->assertEquals($expectedValue, $response->meta->$field);
    }

    private function checkResponseFieldResponse(\stdClass $response, $field, $expectedValue, $type)
    {
        $this->assertObjectHasAttribute($field, $response->response);
        $this->assertInternalType($type, $response->response->$field);
        $this->assertEquals($expectedValue, $response->response->$field);
    }

    private function assertErrorMessage(Result $apiResult, $code, $type, $message, $detail)
    {
        $response = json_decode($apiResult->createResponse()->getContent());
        $this->checkResponseFieldMeta($response, 'http_code', $code, \PHPUnit_Framework_Constraint_IsType::TYPE_INT);

        if (is_null($type)) {
            $this->assertObjectHasAttribute('error_type', $response->meta);
            $this->assertNull($response->meta->error_type);
        } else {
            $this->checkResponseFieldMeta($response, 'error_type', $type, \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        }

        if (is_null($message)) {
            $this->assertObjectHasAttribute('error_message', $response->meta);
            $this->assertNull($response->meta->error_message);
        } else {
            $this->checkResponseFieldMeta($response, 'error_message', $message, \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        }

        if (is_null($detail)) {
            $this->assertObjectHasAttribute('error_details', $response->meta);
            $this->assertNull($response->meta->error_details);
        } else {
            $this->checkResponseFieldMeta($response, 'error_details', $detail, \PHPUnit_Framework_Constraint_IsType::TYPE_STRING);
        }
    }
}
