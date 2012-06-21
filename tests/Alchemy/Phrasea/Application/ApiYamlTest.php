<?php

namespace Alchemy\Phrasea\Application;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/ApiAbstract.inc';

class ApiYamlApplication extends ApiAbstract
{

    public function unserialize($data)
    {
        try {
            $ret = \Symfony\Component\Yaml\Yaml::parse($data);
        } catch (\Exception $e) {
            $this->fail("Unable to parse data : \n" . $data . "\nexception : " . $e->getMessage() . "\n");
        }

        return $ret;
    }

    public function getAcceptMimeType()
    {
        return 'text/yaml';
    }
}
