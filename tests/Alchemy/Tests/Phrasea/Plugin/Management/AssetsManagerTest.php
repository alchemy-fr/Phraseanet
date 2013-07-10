<?php

namespace Alchemy\Tests\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Plugin\Management\AssetsManager;
use Symfony\Component\Filesystem\Exception\IOException;

class AssetsManagerTest extends \PhraseanetPHPUnitAbstract
{
    public function testUpdate()
    {
        $fs = $this->getFilesystemMock();
        $pluginDir = __DIR__ . '/plugin/dir';
        $rootDir = __DIR__ . '/root/dir';

        $fs->expects($this->once())
            ->method('mirror')
            ->with($pluginDir.'/plugin-name/public', $rootDir.'/www/plugins/plugin-name');

        $manifest = $this->getMockBuilder('Alchemy\Phrasea\Plugin\Schema\Manifest')
            ->disableOriginalConstructor()
            ->getMock();
        $manifest->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('plugin-name'));

        $manager = new AssetsManager($fs, $pluginDir, $rootDir);
        $manager->update($manifest);
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testUpdateWithErrors()
    {
        $fs = $this->getFilesystemMock();
        $pluginDir = __DIR__ . '/plugin/dir';
        $rootDir = __DIR__ . '/root/dir';

        $fs->expects($this->once())
            ->method('mirror')
            ->will($this->throwException(new IOException('error')));

        $manifest = $this->getMockBuilder('Alchemy\Phrasea\Plugin\Schema\Manifest')
            ->disableOriginalConstructor()
            ->getMock();
        $manifest->expects($this->any())
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

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testRemoveWithError()
    {
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
        return $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
