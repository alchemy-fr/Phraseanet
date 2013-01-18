<?php

namespace Alchemy\Tests\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Setup\Version\Migration\Migration35;
use Alchemy\Tests\Phrasea\Setup\TestSpecifications;
use Alchemy\Tests\Phrasea\Setup\AbstractSetupTester;

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
