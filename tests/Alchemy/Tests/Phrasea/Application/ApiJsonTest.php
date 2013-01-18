<?php

namespace Alchemy\Tests\Phrasea\Application;

class ApiJsonApplication extends ApiAbstract
{

    public function getParameters(array $parameters = array())
    {
        return $parameters;
    }

    public function unserialize($data)
    {
        return json_decode($data, true);
    }

    public function getAcceptMimeType()
    {
        return 'application/json';
    }
}
