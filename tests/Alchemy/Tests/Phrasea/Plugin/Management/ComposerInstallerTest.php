<?php

namespace Alchemy\Tests\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Plugin\Management\ComposerInstaller;
use Alchemy\Phrasea\Utilities\ComposerSetup;
use Guzzle\Http\Client as Guzzle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Filesystem\Filesystem;

class ComposerInstallerTest extends \PhraseanetTestCase
{
    public function testInstall()
    {
        $fs = new Filesystem();

        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        $vendorDir = __DIR__ . '/../Fixtures/PluginDir/TestPlugin/vendor';
        $installFile =  __DIR__ . '/installer';
        $composer = __DIR__ . '/composer.phar';

        $fs->remove([$composer, $installFile, $vendorDir]);

        if (null === $php) {
            $this->markTestSkipped('Unable to find PHP executable.');
        }

        $setupMock = $this->getMockBuilder('Alchemy\Phrasea\Utilities\ComposerSetup')
            ->disableOriginalConstructor()
            ->getMock();

        $installer = new ComposerInstaller(new ComposerSetup(new Guzzle()), __DIR__, $php);
        $installer->install(__DIR__ . '/../Fixtures/PluginDir/TestPlugin');

        $this->assertFileExists($composer);
        unlink($composer);

        $this->assertFileNotExists($installFile);
        $this->assertFileExists($vendorDir);

        $fs->remove($vendorDir);
    }
}
