<?php

namespace Alchemy\Tests\Phrasea\Setup\Version\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Tests\Phrasea\Setup\AbstractSetupTester;
use Alchemy\Phrasea\Setup\Version\Probe\Probe38;

class Probe38Test extends AbstractSetupTester
{
    public function testNoMigration()
    {
        $probe = $this->getProbe();
        $this->assertFalse($probe->isMigrable());
    }

    public function testMigration()
    {
        $app = new Application('test');
        $app['root.path'] = __DIR__ . '/fixtures-3807';
        $probe = new Probe38($app);
        $this->assertTrue($probe->isMigrable());
        $this->assertInstanceOf('Alchemy\Phrasea\Setup\Version\Migration\Migration38', $probe->getMigration());
    }

    private function getProbe()
    {
        return new Probe38(new Application('test'));
    }
}
