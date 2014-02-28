<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

class ApiJsonTest extends ApiTestCase
{
    protected function getParameters(array $parameters = [])
    {
        return $parameters;
    }

    protected function unserialize($data)
    {
        return json_decode($data, true);
    }

    protected function getAcceptMimeType()
    {
        return 'application/json';
    }
}
