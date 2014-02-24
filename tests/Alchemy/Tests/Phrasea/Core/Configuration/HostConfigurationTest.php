<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Symfony\Component\Yaml\Yaml;

class HostConfigurationTest extends ConfigurationTestCase
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

        return new HostConfiguration(
            new Configuration($yaml, $compiler, $confFile, $compiledFile, $autoreload)
        );
    }

    public function testSetHost()
    {
        $conf = $this->provideConfiguration(__DIR__ . '/Fixtures/configuration-with-hosts.yml');

        $conf->setHost('http://local.dedicated-host');
        $this->assertEquals('Hosted Man !', \igorw\get_in($conf->getConfig(), ['registry', 'general', 'title']));
        $this->assertEquals('http://local.dedicated-host', \igorw\get_in($conf->getConfig(), ['servername']));
        $this->assertFalse(\igorw\get_in($conf->getConfig(), ['main', 'maintenance']));
        $this->assertFalse(\igorw\get_in($conf->getConfig(), ['border-manager', 'enabled']));

        $conf->setHost('local.dedicated-host');
        $this->assertEquals('Hosted Man !', \igorw\get_in($conf->getConfig(), ['registry', 'general', 'title']));
        $this->assertEquals('http://local.dedicated-host', \igorw\get_in($conf->getConfig(), ['servername']));
        $this->assertFalse(\igorw\get_in($conf->getConfig(), ['main', 'maintenance']));
        $this->assertFalse(\igorw\get_in($conf->getConfig(), ['border-manager', 'enabled']));

        $conf->setHost(null);
        $this->assertEquals('SuperPhraseanet', \igorw\get_in($conf->getConfig(), ['registry', 'general', 'title']));
        $this->assertEquals('http://local.phrasea/', \igorw\get_in($conf->getConfig(), ['servername']));
        $this->assertFalse(\igorw\get_in($conf->getConfig(), ['main', 'maintenance']));
        $this->assertTrue(\igorw\get_in($conf->getConfig(), ['border-manager', 'enabled']));
    }
}
