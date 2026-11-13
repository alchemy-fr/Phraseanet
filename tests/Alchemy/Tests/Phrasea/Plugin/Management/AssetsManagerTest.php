<?php

namespace Alchemy\Tests\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Plugin\Management\AssetsManager;
use Symfony\Component\Filesystem\Exception\IOException;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class AssetsManagerTest extends \PhraseanetTestCase
{
    public function testUpdate()
    {
        $fs = $this->getFilesystemMock();
        $pluginDir = __DIR__ . '/plugin/dir';
        $rootDir = __DIR__ . '/root/dir';

        $fs->expects($this->once())
            ->method('mirror')
            ->with($pluginDir.'/plugin-name/public', $rootDir.'/www/plugins/plugin-name');

        $manifest = $this->createMock('Alchemy\Phrasea\Plugin\Schema\Manifest');
        $manifest
            ->method('getName')
            ->will($this->returnValue('plugin-name'));

        $manager = new AssetsManager($fs, $pluginDir, $rootDir);
        $manager->update($manifest);
    }

    public function testUpdateWithErrors()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\RuntimeException::class);
        $fs = $this->getFilesystemMock();
        $pluginDir = __DIR__ . '/plugin/dir';
        $rootDir = __DIR__ . '/root/dir';

        $fs->expects($this->once())
            ->method('mirror')
            ->will($this->throwException(new IOException('error')));

        $manifest = $this->createMock('Alchemy\Phrasea\Plugin\Schema\Manifest');
        $manifest
            ->method('getName')
            ->will($this->returnValue('plugin-name'));

        $manager = new AssetsManager($fs, $pluginDir, $rootDir);
        $manager->update($manifest);
    }

    public function testRemove()
    {
        $fs = $this->getFilesystemMock();
        $pluginDir = __DIR__ . '/plugin/dir';
        $rootDir = __DIR__ . '/root/dir';

        $fs->expects($this->once())
            ->method('remove')
            ->with($rootDir.'/www/plugins/plugin-name');

        $manager = new AssetsManager($fs, $pluginDir, $rootDir);
        $manager->remove('plugin-name');
    }

    public function testRemoveWithError()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\RuntimeException::class);
        $fs = $this->getFilesystemMock();
        $pluginDir = __DIR__ . '/plugin/dir';
        $rootDir = __DIR__ . '/root/dir';

        $fs->expects($this->once())
            ->method('remove')
            ->will($this->throwException(new IOException('error')));

        $manager = new AssetsManager($fs, $pluginDir, $rootDir);
        $manager->remove('plugin-name');
    }

    public function testTwigFunction()
    {
        $this->assertEquals('/plugins/plugin-name/path/to/asset.png', AssetsManager::twigPluginAsset('plugin-name', 'path/to/asset.png'));
        $this->assertEquals('/plugins/plugin-name/path/to/asset.png', AssetsManager::twigPluginAsset('plugin-name', '/path/to/asset.png'));
    }

    private function getFilesystemMock()
    {
        return $this->createMock('Alchemy\Phrasea\Filesystem\PhraseanetFilesystem');
    }
}
