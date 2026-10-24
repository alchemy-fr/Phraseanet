<?php

namespace Alchemy\Tests\Phrasea\Setup;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\ConfigurationTester;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ConfigurationTesterTest extends AbstractSetupTester
{

    public function testStatus()
    {
        $tester = $this->getTester();
        $this->assertFalse($tester->isMigrable());
        $this->assertFalse($tester->isBlank());
        $this->assertTrue($tester->isInstalled());
        $this->assertTrue($tester->isUpToDate());
        $this->assertFalse($tester->isUpgradable());
    }

    public function testNotMigrableEvenIfOldFilesFrom31Present()
    {
        $gvFile = __DIR__ . '/../../../../../config/_GV.php';
        $connexionFile = __DIR__ . '/../../../../../config/connexion.inc';

        file_put_contents($connexionFile, "");
        file_put_contents($gvFile, "");

        $tester = $this->getTester();
        $this->assertFalse($tester->isMigrable());
        $this->assertFalse($tester->isBlank());
        $this->assertTrue($tester->isInstalled());
        $this->assertTrue($tester->isUpToDate());
        $this->assertFalse($tester->isUpgradable());

        unlink($gvFile);
        unlink($connexionFile);
    }

    public function testUninstalled()
    {
        $tester = $this->getTester();

        $this->uninstall();

        $this->assertFalse($tester->isMigrable());
        $this->assertTrue($tester->isBlank());
        $this->assertFalse($tester->isInstalled());
        $this->assertFalse($tester->isUpToDate());
        $this->assertFalse($tester->isUpgradable());
    }

    public function test31()
    {
        $tester = $this->getTester();

        $this->goBackTo31();

        $this->assertTrue($tester->isMigrable());
        $this->assertFalse($tester->isBlank());
        $this->assertFalse($tester->isInstalled());
        $this->assertFalse($tester->isUpToDate());
        $this->assertFalse($tester->isUpgradable());
    }

    public function testNewMigration()
    {
        $probe = $this->createMock('Alchemy\\Phrasea\\Setup\\Version\\Probe\\ProbeInterface');
        $probe
            ->method('isMigrable')
            ->will($this->returnValue(true));

        $app = new Application(Application::ENV_TEST);

        $app['phraseanet.version'] = $this->createStub('Alchemy\\Phrasea\\Core\\Version');

        // Must return version + 1
        $app['phraseanet.version']
            ->method('getNumber')
            ->will($this->returnValue('4.2'));

        $tester = $this->getTester($app);

        $tester->registerVersionProbe($probe);

        $this->assertTrue($tester->isMigrable());
    }

    public function test35()
    {
        $tester = $this->getTester();

        $this->goBackTo35();

        $this->assertTrue($tester->isMigrable());
        $this->assertFalse($tester->isBlank());
        $this->assertFalse($tester->isInstalled());
        $this->assertFalse($tester->isUpToDate());
        $this->assertFalse($tester->isUpgradable());
    }

    public function testUpgradable()
    {
        $app = new Application(Application::ENV_TEST);

        $app['phraseanet.version'] = $this->createStub('Alchemy\\Phrasea\\Core\\Version');

        /**
         * Must return version + 1
         */
        $app['phraseanet.version']
            ->method('getNumber')
            ->will($this->returnValue('4.2'));

        $tester = $this->getTester($app);

        $this->assertFalse($tester->isMigrable());
        $this->assertFalse($tester->isBlank());
        $this->assertTrue($tester->isInstalled());
        $this->assertFalse($tester->isUpToDate());
        $this->assertTrue($tester->isUpgradable());
    }

    public function testGetRequirements()
    {
        $app = new Application(Application::ENV_TEST);
        $app['phraseanet.SE'] = $this->createSearchEngineMock();

        foreach ($this->getTester($app)->getRequirements() as $requirements) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementCollectionInterface', $requirements);
        }
    }

    private function getTester(?Application $app = null)
    {
        $app = $app ? : new Application(Application::ENV_TEST);

        return new ConfigurationTester($app);
    }
}
