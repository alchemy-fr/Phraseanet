<?php

namespace Alchemy\Tests\Phrasea\Controller\Api;

use Symfony\Component\Yaml\Yaml;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class ApiYamlTest extends ApiTestCase
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
