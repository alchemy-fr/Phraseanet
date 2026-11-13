<?php

namespace Alchemy\Tests\Phrasea\Command;

use Alchemy\Phrasea\Command\UpgradeDBDatas;
use Alchemy\Phrasea\Command\Upgrade\Step31;
use Alchemy\Phrasea\Command\Upgrade\Step35;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class UpgradeDBDatasTest extends \PhraseanetTestCase
{
    /**
     * @var UpgradeDBDatas
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new UpgradeDBDatas('commandname');
    }

    public function testSetUpgrades()
    {
        $this->object->setUpgrades([]);
        $this->assertEquals([], $this->object->getUpgrades());

        $upgrades = [
            new Step31($this->loadApp())
        ];
        $this->object->setUpgrades($upgrades);
        $this->assertEquals($upgrades, $this->object->getUpgrades());
    }

    public function testAddUpgrade()
    {
        $this->assertEquals([], $this->object->getUpgrades());

        $step31 = new Step31($this->loadApp());
        $this->object->addUpgrade($step31);

        $this->assertEquals([$step31], $this->object->getUpgrades());

        $step35 = new Step35($this->loadApp());
        $this->object->addUpgrade($step35);

        $this->assertEquals([$step31, $step35], $this->object->getUpgrades());
    }

    /**
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
