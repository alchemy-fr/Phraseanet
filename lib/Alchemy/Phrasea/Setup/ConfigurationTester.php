<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;

class ConfigurationTester
{

    private $app;
    private $probes;
    private $versionProbes;

    public function __construct(Application $app)
    {
        $this->app = $app;

    }

    public function registerProbe(ProbeInterface $probe)
    {
        $this->probes[] = $probe;
    }

    public function isInstalled()
    {
        return file_exists(__DIR__ . '/../../../../config/config.yml')
            && file_exists(__DIR__ . '/../../../../config/connexions.yml')
            && file_exists(__DIR__ . '/../../../../config/services.yml');
    }

    public function probeIsMigrable()
    {

    }
}
