<?php

namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\PluginManager;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;

class PluginManagerTest extends PluginTestCase
{
    public function testListGoodPlugins()
    {
        $prevPlugins = self::$DI['cli']['conf']->get('plugins');
        self::$DI['cli']['conf']->set('plugins', []);
        self::$DI['cli']['conf']->set(['plugins', 'test-plugin', 'enabled'], true);

        $manager = new PluginManager(__DIR__ . '/Fixtures/PluginDirInstalled', self::$DI['cli']['plugins.plugins-validator'], self::$DI['cli']['conf']);
        $plugins = $manager->listPlugins();
        $this->assertCount(1, $plugins);
        $plugin = array_pop($plugins);

        $this->assertFalse($plugin->isErroneous());

        self::$DI['cli']['conf']->set('plugins', $prevPlugins);
    }

    public function testListWrongPlugins()
    {
        $prevPlugins = self::$DI['cli']['conf']->get('plugins');
        self::$DI['cli']['conf']->set('plugins', []);
        self::$DI['cli']['conf']->set(['plugins', 'plugin-test', 'enabled'], true);
        self::$DI['cli']['conf']->set(['plugins', 'plugin-test2', 'enabled'], true);
        self::$DI['cli']['conf']->set(['plugins', 'plugin-test3', 'enabled'], true);

        $manager = new PluginManager(__DIR__ . '/Fixtures/WrongPlugins', self::$DI['cli']['plugins.plugins-validator'], self::$DI['cli']['conf']);
        $plugins = $manager->listPlugins();
        $this->assertCount(3, $plugins);
        $plugin = array_pop($plugins);

        $this->assertTrue($plugin->isErroneous());

        self::$DI['cli']['conf']->set('plugins', $prevPlugins);
    }

    public function testHasPlugin()
    {
        $manager = new PluginManager(__DIR__ . '/Fixtures/PluginDirInstalled', self::$DI['cli']['plugins.plugins-validator'], self::$DI['cli']['conf']);
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
