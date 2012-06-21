<?php

namespace Alchemy\Phrasea\Application;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/ApiAbstract.inc';

class ApiJsonApplication extends ApiAbstract
{

    public function unserialize($data)
    {
        return json_decode($data, true);
    }

    public function getAcceptMimeType()
    {
        return 'application/json';
    }
}
