<?php

namespace Alchemy\Tests\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator;
use Alchemy\Phrasea\Plugin\Schema\Manifest;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\ExecutableFinder;

class AutoloaderGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneratedFileAfterInstall()
    {
        $pluginDir = __DIR__ . '/../Fixtures/PluginDirInstalled/TestPlugin';
        $pluginsDir = __DIR__ . '/../Fixtures/PluginDirInstalled';

        $files = array($pluginsDir . '/services.php', $pluginsDir . '/autoload.php');

        $this->cleanup($files);

        $generator = new AutoloaderGenerator($pluginsDir);
        $generator->write(array(new Manifest(json_decode(file_get_contents($pluginDir . '/manifest.json'), true))));

        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Php executable not found.');
        }

        foreach ($files as $file ) {
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
