<?php

namespace Alchemy\Tests\Phrasea\Application;

class ApiYamlApplication extends ApiAbstract
{

    public function getParameters(array $parameters = array())
    {
        return $parameters;
    }

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
