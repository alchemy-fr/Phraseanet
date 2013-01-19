<?php

namespace Alchemy\Tests\Phrasea\Setup\Version\Probe;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Version\Probe\Probe35;
use Alchemy\Tests\Phrasea\Setup\AbstractSetupTester;

class Probe35Test extends AbstractSetupTester
{
    public function testNoMigration()
    {
        $probe = $this->getProbe();
        $this->assertFalse($probe->isMigrable());
    }

    public function testMigration()
    {
        $this->goBackTo35();
        $probe = $this->getProbe();
        $this->assertTrue($probe->isMigrable());
        $this->assertInstanceOf('Alchemy\Phrasea\Setup\Version\Migration\Migration35', $probe->getMigration());
    }

    private function getProbe()
    {
        return new Probe35(new Application('test'));
    }
}
