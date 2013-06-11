<?php

namespace Alchemy\Tests\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Setup\Version\Migration\Migration35;
use Alchemy\Tests\Phrasea\Setup\AbstractSetupTester;
use Symfony\Component\Yaml\Yaml;

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
        $config = __DIR__ . '/configuration.yml';
        $compiled = __DIR__ . '/configuration.yml.php';

        @unlink($config);
        @unlink($compiled);

        $this->specifications = new Configuration(new Yaml(), new Compiler(), $config, $compiled, true);
        $this->assertFalse($this->specifications->isSetup());

        $this->goBackTo35();
        $app = new Application('test');
        $migration = $this->getMigration($app);
        $migration->migrate();

        @unlink(__DIR__ . '/../../../../../../config/config.inc.old');
        @unlink(__DIR__ . '/../../../../../../config/connexion.inc.old');

        $this->assertTrue($this->specifications->isSetup());

        @unlink($config);
        @unlink($compiled);
    }

    private function getMigration(Application $app = null)
    {
        $app = $app ? : new Application('test');

        if ($this->specifications) {
            $app['phraseanet.configuration'] = $this->specifications;
        }

        return new Migration35($app);
    }
}
