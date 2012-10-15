<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Core\Configuration\ApplicationSpecification;

class TestSpecifications extends ApplicationSpecification
{
    private $rootDir;

    public function __construct()
    {
        parent::__construct();
        $this->rootDir = sys_get_temp_dir() . '/' . microtime(true);
    }

    public function __destruct()
    {
        @unlink($this->getConfigurationsPathFile());
        @unlink($this->getServicesPathFile());
        @unlink($this->getConnexionsPathFile());
    }

    protected function getConfigurationsPathFile()
    {
        return $this->rootDir . 'config.yml';
    }

    protected function getConnexionsPathFile()
    {
        return $this->rootDir . 'connexions.yml';
    }

    protected function getServicesPathFile()
    {
        return $this->rootDir . 'services.yml';
    }

    protected function getBinariesPathFile()
    {
        return $this->rootDir . 'binaries.yml';
    }
}
