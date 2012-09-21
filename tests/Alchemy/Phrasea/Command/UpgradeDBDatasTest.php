<?php

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Command\Upgrade\Step31;
use Alchemy\Phrasea\Command\Upgrade\Step35;

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

        $upgrades = array(
            new Step31(new Application('test'))
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

        $step31 = new Step31(new Application('test'));
        $this->object->addUpgrade($step31);

        $this->assertEquals(array($step31), $this->object->getUpgrades());

        $step35 = new Step35(new Application('test'));
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
