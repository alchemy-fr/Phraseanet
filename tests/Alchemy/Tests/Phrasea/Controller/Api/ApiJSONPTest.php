<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Symfony\Component\HttpFoundation\Response;

class ApiJSONPTest extends ApiTestCase
{
    protected function evaluateResponseBadRequest(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code 400 ' . $response->getContent());
    }

    protected function evaluateResponseForbidden(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code 403 ' . $response->getContent());
    }

    protected function evaluateResponseNotFound(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code 404 ' . $response->getContent());
    }

    protected function evaluateResponseMethodNotAllowed(Response $response)
    {
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code 405 ' . $response->getContent());
    }

    protected function getParameters(array $parameters = [])
    {
        $parameters['callback'] = 'jsFunction';

        return $parameters;
    }

    protected function unserialize($data)
    {
        if (strpos($data, 'jsFunction(') !== 0) {
            $this->fail('Invalid JSONP response');
        }

        if (substr($data, -1) !== ')') {
            $this->fail('Invalid JSONP response');
        }

        return json_decode(substr($data, 11, -1), true);
    }

    protected function getAcceptMimeType()
    {
        return 'application/json';
    }
}
