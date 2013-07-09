<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\PluginServiceProvider;
use Silex\Application;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @covers Alchemy\Phrasea\Core\Provider\PluginServiceProvider
 */
class PluginServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.json-validator',
                'JsonSchema\Validator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.plugins-validator',
                'Alchemy\Phrasea\Plugin\Schema\PluginValidator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.manifest-validator',
                'Alchemy\Phrasea\Plugin\Schema\ManifestValidator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.import-strategy',
                'Alchemy\Phrasea\Plugin\Importer\ImportStrategy'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.autoloader-generator',
                'Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.guzzle',
                'Guzzle\Http\Client'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.composer-installer',
                'Alchemy\Phrasea\Plugin\Management\ComposerInstaller'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.explorer',
                'Alchemy\Phrasea\Plugin\Management\PluginsExplorer'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.importer',
                'Alchemy\Phrasea\Plugin\Importer\Importer'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.importer.folder-importer',
                'Alchemy\Phrasea\Plugin\Importer\FolderImporter'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PluginServiceProvider',
                'plugins.assets-manager',
                'Alchemy\Phrasea\Plugin\Management\AssetsManager'
            )
        );
    }

    public function testSchemaIsDefined()
    {
        $app = new Application();
        $app->register(new PluginServiceProvider());

        $this->assertFileExists($app['plugins.schema']);
        $this->assertTrue(is_file($app['plugins.schema']));
    }

    public function testPluginDirIsDefined()
    {
        $app = new Application();
        $app->register(new PluginServiceProvider());

        $this->assertFileExists($app['plugins.directory']);
        $this->assertTrue(is_dir($app['plugins.directory']));
    }

    public function testInstallerUsesPhpConf()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to detect PHP binary');
        }

        $app = new Application();
        $app['phraseanet.configuration'] = array('binaries' => array('php_binary' => null));
        $app->register(new PluginServiceProvider());
        $this->assertInstanceOf('Alchemy\Phrasea\Plugin\Management\ComposerInstaller', $app['plugins.composer-installer']);
    }

    public function testInstallerCanDetectPhpConf()
    {
        $app = new Application();
        $app['phraseanet.configuration'] = array('binaries' => array('php_binary' => null));
        $app->register(new PluginServiceProvider());
        $this->assertInstanceOf('Alchemy\Phrasea\Plugin\Management\ComposerInstaller', $app['plugins.composer-installer']);
    }

    private function createRegistryMock()
    {
        return $this->getMockBuilder('registry')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
