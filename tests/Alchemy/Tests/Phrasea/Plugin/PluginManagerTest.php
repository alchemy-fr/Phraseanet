<?php

namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\PluginManager;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;

class PluginManagerTest extends PluginTestCase
{
    public function testListGoodPlugins()
    {
        $manager = new PluginManager(__DIR__ . '/Fixtures/PluginDirInstalled', self::$DI['cli']['plugins.plugins-validator']);
        $plugins = $manager->listPlugins();
        $this->assertCount(1, $plugins);
        $plugin = array_pop($plugins);

        $this->assertFalse($plugin->isErroneous());
    }

    public function testListWrongPlugins()
    {
        $manager = new PluginManager(__DIR__ . '/Fixtures/WrongPlugins', self::$DI['cli']['plugins.plugins-validator']);
        $plugins = $manager->listPlugins();
        $this->assertCount(8, $plugins);
        $plugin = array_pop($plugins);

        $this->assertTrue($plugin->isErroneous());
    }

    public function testHasPlugin()
    {
        $manager = new PluginManager(__DIR__ . '/Fixtures/PluginDirInstalled', self::$DI['cli']['plugins.plugins-validator']);
        $this->assertTrue($manager->hasPlugin('test-plugin'));
        $this->assertFalse($manager->hasPlugin('test-plugin2'));
    }

    private function createValidatorMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Schema\PluginValidator')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
