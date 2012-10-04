<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;

require_once __DIR__ . '/AbstractSetupTester.inc';

class ConfigurationTesterTest extends AbstractSetupTester
{

    /**
     * @covers Alchemy\Phrasea\Setup\ConfigurationTester
     */
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
        $gvFile = __DIR__ . '/../../../../config/_GV.php';
        $connexionFile = __DIR__ . '/../../../../config/connexion.inc';

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

    /**
     * @covers Alchemy\Phrasea\Setup\ConfigurationTester
     */
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

    /**
     * @covers Alchemy\Phrasea\Setup\ConfigurationTester
     */
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

    /**
     * @covers Alchemy\Phrasea\Setup\ConfigurationTester
     */
    public function testNewMigration()
    {
        $probe = $this->getMockBuilder('Alchemy\\Phrasea\\Setup\\Version\\Probe\\ProbeInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $probe->expects($this->any())
            ->method('isMigrable')
            ->will($this->returnValue(true));

        $app = new Application('test');

        $app['phraseanet.version'] = $this->getMockBuilder('Alchemy\\Phrasea\\Core\\Version')
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Must return version + 1
         */
        $app['phraseanet.version']->expects($this->any())
            ->method('getNumber')
            ->will($this->returnValue('3.9'));

        $tester = $this->getTester($app);
        
        $tester->registerVersionProbe($probe);

        $this->assertTrue($tester->isMigrable());
    }

    /**
     * @covers Alchemy\Phrasea\Setup\ConfigurationTester
     */
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

    /**
     * @covers Alchemy\Phrasea\Setup\ConfigurationTester
     */
    public function testUpgradable()
    {
        $app = new Application('test');

        $app['phraseanet.version'] = $this->getMockBuilder('Alchemy\\Phrasea\\Core\\Version')
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Must return version + 1
         */
        $app['phraseanet.version']->expects($this->any())
            ->method('getNumber')
            ->will($this->returnValue('3.9'));

        $tester = $this->getTester($app);

        $this->assertFalse($tester->isMigrable());
        $this->assertFalse($tester->isBlank());
        $this->assertTrue($tester->isInstalled());
        $this->assertFalse($tester->isUpToDate());
        $this->assertTrue($tester->isUpgradable());
    }

    private function getTester(Application $app = null)
    {
        $app = $app? : new Application('test');
        return new ConfigurationTester($app);
    }
}
