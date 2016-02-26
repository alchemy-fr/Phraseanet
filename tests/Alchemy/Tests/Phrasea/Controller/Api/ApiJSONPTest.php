<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group legacy
 * @group web
 */
class ApiJSONPTest extends ApiTestCase
{
    protected function getParameters(array $parameters = [])
    {
        $parameters['callback'] = 'jsFunction';

        return $parameters;
    }

    protected function unserialize($data)
    {
        if (strpos($data, 'jsFunction(') !== 4) {
            $this->fail('Invalid JSONP response');
        }

        if (substr($data, -2) !== ');') {
            $this->fail('Invalid JSONP response');
        }

        return json_decode(substr($data, 15, -2), true);
    }

    protected function getAcceptMimeType()
    {
        return 'application/json';
    }
}
