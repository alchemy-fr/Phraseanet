<?php

namespace Alchemy\Phrasea\Setup;

use Alchemy\Phrasea\Application;

class ConfigurationTesterTest extends \PHPUnit_Framework_TestCase
{
    private $tearDownHandlers = array();

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        foreach ($this->tearDownHandlers as $handler) {
            $handler();
        }

        parent::tearDown();
    }

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

        $tester = $this->getTester();
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

    private function uninstall()
    {
        rename(__DIR__ . '/../../../../config/config.yml', __DIR__ . '/../../../../config/config.yml.test');
        rename(__DIR__ . '/../../../../config/connexions.yml', __DIR__ . '/../../../../config/connexions.yml.test');
        rename(__DIR__ . '/../../../../config/services.yml', __DIR__ . '/../../../../config/services.yml.test');

        $this->tearDownHandlers[] = function() {
                rename(__DIR__ . '/../../../../config/config.yml.test', __DIR__ . '/../../../../config/config.yml');
                rename(__DIR__ . '/../../../../config/connexions.yml.test', __DIR__ . '/../../../../config/connexions.yml');
                rename(__DIR__ . '/../../../../config/services.yml.test', __DIR__ . '/../../../../config/services.yml');
            };
    }

    private function goBackTo31()
    {
        $this->uninstall();

        file_put_contents(__DIR__ . '/../../../../config/_GV.php', "<?php\n");
        file_put_contents(__DIR__ . '/../../../../config/connexion.inc', "<?php\n");

        $this->tearDownHandlers[] = function() {
                unlink(__DIR__ . '/../../../../config/_GV.php');
                unlink(__DIR__ . '/../../../../config/connexion.inc');
            };
    }

    private function goBackTo35()
    {
        $this->uninstall();

        file_put_contents(__DIR__ . '/../../../../config/config.inc', "<?php\n\$servername = 'http://local.phrasea';\n");
        file_put_contents(__DIR__ . '/../../../../config/connexion.inc', "<?php\n");

        $this->tearDownHandlers[] = function() {
                unlink(__DIR__ . '/../../../../config/config.inc');
                unlink(__DIR__ . '/../../../../config/connexion.inc');
            };
    }

    private function getTester(Application $app = null)
    {
        $app = $app? : new Application('test');
        return new ConfigurationTester($app);
    }
}
