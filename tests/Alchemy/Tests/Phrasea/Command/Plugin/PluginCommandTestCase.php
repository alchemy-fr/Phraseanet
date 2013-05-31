<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

class PluginCommandTestCase extends \PhraseanetPHPUnitAbstract
{
    protected function createTemporaryFilesystemMock()
    {
        return $this->getMockBuilder('Neutron\TemporaryFilesystem\TemporaryFilesystem')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createPluginsImporterMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Importer\Importer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createPluginsValidatorMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Schema\PluginValidator')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createManifestMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Schema\Manifest')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createComposerInstallerMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Management\ComposerInstaller')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFilesystemMock()
    {
        return $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createPluginsExplorerMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Management\PluginsExplorer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createPluginsAutoloaderGeneratorMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
