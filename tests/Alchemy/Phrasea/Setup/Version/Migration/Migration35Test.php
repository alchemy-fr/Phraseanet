<?php

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Core\Configuration\ApplicationSpecification;
use Alchemy\Phrasea\Setup\AbstractSetupTester;

require_once __DIR__ . '/../../AbstractSetupTester.inc';

class Migration35Test extends AbstractSetupTester
{
    private $specifications;

    public function tearDown()
    {
        if ($this->specifications) {
            $this->specifications->delete();
        }
        parent::tearDown();
    }

    public function testMigrateFails()
    {
        $migration = $this->getMigration();
        try {
            $migration->migrate();
            $this->fail('Should fail');
        } catch (\LogicException $e) {

        }
    }

    public function testMigrate()
    {
        $this->specifications = new TestSpecifications();
        $this->assertFalse($this->specifications->isSetup());

        $this->goBackTo35();
        $app = new Application('test');
        $migration = $this->getMigration($app);
        $migration->migrate();

        @unlink(__DIR__ . '/../../../../../../config/config.inc.old');
        @unlink(__DIR__ . '/../../../../../../config/connexion.inc.old');

        $this->assertTrue($this->specifications->isSetup());
    }

    private function getMigration(Application $app = null)
    {
        $app = $app ? : new Application('test');

        if ($this->specifications) {
            $app['phraseanet.configuration'] = new Configuration($this->specifications);
        }

        return new Migration35($app);
    }
}

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
}
