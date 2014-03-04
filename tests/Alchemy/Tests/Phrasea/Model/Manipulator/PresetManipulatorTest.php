<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\PresetManipulator;

class PresetManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = new PresetManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.presets']);
        $this->assertCount(0, self::$DI['app']['repo.presets']->findAll());
        $fields = [
            ['name' => 'titi', 'value' => 'titi_value'], ['name' => 'tutu', 'value' => 'tutu_value'],
            ['name' => 'titi', 'value' => 'titi2_value'], ['name' => 'tutu', 'value' => 'tutu2_value'],
        ];
        $title = 'title';
        $preset = $manipulator->create(self::$DI['user'], self::$DI['collection']->get_sbas_id(), $title, $fields);
        $this->assertEquals($title, $preset->getTitle());
        $this->assertEquals($fields, $preset->getData());
        $this->assertEquals(self::$DI['collection']->get_sbas_id(), $preset->getSbasId());
        $this->assertEquals(self::$DI['user']->getid(), $preset->getUser()->getId());
        $this->assertCount(1, self::$DI['app']['repo.presets']->findAll());
    }

    public function testDelete()
    {
        $manipulator = new PresetManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.presets']);
        $preset = $manipulator->create(self::$DI['user'], self::$DI['collection']->get_sbas_id(), 'title', []);
        $countBefore = count(self::$DI['app']['repo.presets']->findAll());
        $manipulator->delete($preset);
        $this->assertGreaterThan(count(self::$DI['app']['repo.presets']->findAll()), $countBefore);
    }
}
