<?php

namespace Alchemy\Tests\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\CLI;
use Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator;
use Alchemy\Phrasea\Plugin\Schema\Manifest;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\ExecutableFinder;

class AutoloaderGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratedFileAfterInstall()
    {
        $pluginDir = __DIR__ . '/../Fixtures/PluginDirInstalled/test-plugin';
        $pluginsDir = __DIR__ . '/../Fixtures/PluginDirInstalled';

        $files = array(
            $pluginsDir . '/services.php',
            $pluginsDir . '/autoload.php',
            $pluginsDir . '/commands.php',
            $pluginsDir . '/twig-paths.php',
            $pluginsDir . '/login.less',
            $pluginsDir . '/account.less',
        );

        $this->cleanup($files);

        $generator = new AutoloaderGenerator($pluginsDir);
        $generator->write(array(new Manifest(json_decode(file_get_contents($pluginDir . '/manifest.json'), true))));

        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Php executable not found.');
        }

        foreach ($files as $file) {
            $this->assertFileExists($file);
            $process = ProcessBuilder::create(array($php, '-l', $file))->getProcess();
            $process->run();
            $this->assertTrue($process->isSuccessful(), basename($file) . ' is valid');
        }

        // test autoload
        $this->assertFalse(class_exists('Vendor\PluginService'));
        $loader = require $pluginsDir . '/autoload.php';
        $this->assertInstanceOf('Composer\Autoload\ClassLoader', $loader);
        $this->assertTrue(class_exists('Vendor\PluginService'));

        // load services
        $app = new Application();
        $retrievedApp = require $pluginsDir . '/services.php';

        $this->assertSame($app, $retrievedApp);
        $this->assertEquals('hello world', $app['plugin-test']);

        // load services
        $cli = new CLI('test');
        $retrievedCli = require $pluginsDir . '/commands.php';

        $this->assertSame($cli, $retrievedCli);
        $this->assertInstanceOf('Vendor\CustomCommand', $cli['console']->find('hello:world'));

        $mapping = require $pluginsDir . '/twig-paths.php';
        $this->assertSame(array('plugin-test-plugin' => realpath($pluginsDir) . '/test-plugin/views', realpath($pluginsDir) . '/test-plugin/views', realpath($pluginsDir) . '/test-plugin/twig-views'), $mapping);

        $this->assertRegExp('#@import#', file_get_contents($pluginsDir . '/login.less'));
        $this->assertRegExp('#@import#', file_get_contents($pluginsDir . '/account.less'));

        $this->cleanup($files);
    }

    private function cleanup($files)
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
