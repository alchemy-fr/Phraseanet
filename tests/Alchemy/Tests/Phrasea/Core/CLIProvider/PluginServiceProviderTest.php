<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider
 */
class PluginServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.import-strategy',
                'Alchemy\Phrasea\Plugin\Importer\ImportStrategy'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.autoloader-generator',
                'Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.composer-installer',
                'Alchemy\Phrasea\Plugin\Management\ComposerInstaller'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.explorer',
                'Alchemy\Phrasea\Plugin\Management\PluginsExplorer'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.importer',
                'Alchemy\Phrasea\Plugin\Importer\Importer'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.importer.folder-importer',
                'Alchemy\Phrasea\Plugin\Importer\FolderImporter'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\PluginServiceProvider',
                'plugins.assets-manager',
                'Alchemy\Phrasea\Plugin\Management\AssetsManager'
            ]
        ];
    }

    public function testSchemaIsDefined()
    {
        $app = self::$DI['cli'];
        $app->register(new PluginServiceProvider());

        $this->assertFileExists($app['plugins.schema']);
        $this->assertTrue(is_file($app['plugins.schema']));
    }

    public function testPluginDirIsDefined()
    {
        $app = self::$DI['cli'];
        $app->register(new PluginServiceProvider());

        $this->assertFileExists($app['plugin.path']);
        $this->assertTrue(is_dir($app['plugin.path']));
    }

    public function testInstallerUsesPhpConf()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to detect PHP binary');
        }

        $app = self::$DI['cli'];

        $bkp = $app['conf']->get(['main', 'binaries']);

        $app['conf']->set(['main', 'binaries', 'php_binary'], null);
        $app->register(new PluginServiceProvider());
        $this->assertInstanceOf('Alchemy\Phrasea\Plugin\Management\ComposerInstaller', $app['plugins.composer-installer']);

        $app['conf']->set(['main', 'binaries'], $bkp);
    }

    public function testInstallerCanDetectPhpConf()
    {
        $app = self::$DI['cli'];

        $bkp = $app['conf']->get(['main', 'binaries']);

        $app['conf']->set(['main', 'binaries', 'php_binary'], null);
        $app->register(new PluginServiceProvider());
        $this->assertInstanceOf('Alchemy\Phrasea\Plugin\Management\ComposerInstaller', $app['plugins.composer-installer']);

        $app['conf']->set(['main', 'binaries'], $bkp);
    }
}
