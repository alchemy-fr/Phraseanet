<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends ConfigurationTestCase
{
    protected function provideConfiguration($confFile, $compiledFile = null, Compiler $compiler = null, Yaml $yaml = null, $autoreload = false)
    {
        if (null === $compiledFile) {
            $compiledFile = $this->compiled;
        }

        if (null === $yaml) {
            $yaml = new Yaml();
        }
        if (null === $compiler) {
            $compiler = new Compiler();
        }

        return new Configuration($yaml, $compiler, $confFile, $compiledFile, $autoreload);
    }

    public function testGetTestConnectionConf()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';

        $conf = $this->provideConfiguration($configFile, null, null, null, true);
        $data = $conf->getTestConnectionParameters();

        $this->assertArrayHasKey('driver', $data);
        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('charset', $data);
    }
}
