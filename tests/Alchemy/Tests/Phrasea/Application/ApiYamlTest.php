<?php

namespace Alchemy\Tests\Phrasea\Application;

use Symfony\Component\Yaml\Yaml;

class ApiYamlApplication extends ApiTestCase
{
    protected function getParameters(array $parameters = [])
    {
        return $parameters;
    }

    protected function unserialize($data)
    {
        return Yaml::parse($data);
    }

    protected function getAcceptMimeType()
    {
        return 'text/yaml';
    }
}
