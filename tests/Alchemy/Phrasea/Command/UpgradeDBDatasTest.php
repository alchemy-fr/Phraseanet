<?php

namespace Alchemy\Phrasea\Command;

class UpgradeDBDatasTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UpgradeDBDatas
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::__construct
     */
    protected function setUp()
    {
        $this->object = new UpgradeDBDatas('commandname');
    }

    /**
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::requireSetup
     */
    public function testRequireSetup()
    {
        $this->assertInternalType('boolean', $this->object->requireSetup());
    }

    /**
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::setUpgrades
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::addUpgrade
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::getUpgrades
     */
    public function testSetUpgrades()
    {
        $this->object->setUpgrades(array());
        $this->assertEquals(array(), $this->object->getUpgrades());

        $core = \bootstrap::getCore();

        $upgrades = array(
            new Upgrade\Step31($core, $core['monolog'])
        );
        $this->object->setUpgrades($upgrades);
        $this->assertEquals($upgrades, $this->object->getUpgrades());
    }

    /**
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::addUpgrade
     */
    public function testAddUpgrade()
    {
        $this->assertEquals(array(), $this->object->getUpgrades());

        $core = \bootstrap::getCore();

        $step31 = new Upgrade\Step31($core, $core['monolog']);
        $this->object->addUpgrade($step31);

        $this->assertEquals(array($step31), $this->object->getUpgrades());

        $step35 = new Upgrade\Step35($core, $core['monolog']);
        $this->object->addUpgrade($step35);

        $this->assertEquals(array($step31, $step35), $this->object->getUpgrades());
    }

    /**
     * @covers Alchemy\Phrasea\Command\UpgradeDBDatas::execute
     * @todo Implement testExecute().
     */
    public function testExecute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
