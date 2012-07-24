<?php

namespace Alchemy\Phrasea\Application;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/ApiAbstract.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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
