<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PluginCommandTestCase extends \PhraseanetTestCase
{
    protected function createTemporaryFilesystemMock()
    {
        return $this->createMock('Neutron\TemporaryFilesystem\TemporaryFilesystem');
    }

    protected function createPluginsImporterMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Importer\Importer');
    }

    protected function createPluginsValidatorMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Schema\PluginValidator');
    }

    protected function createManifestMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Schema\Manifest');
    }

    protected function createComposerInstallerMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Management\ComposerInstaller');
    }

    protected function createFilesystemMock()
    {
        return $this->createMock('Alchemy\Phrasea\Filesystem\PhraseanetFilesystem');
    }

    protected function createPluginsExplorerMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Management\PluginsExplorer');
    }

    protected function createPluginsAutoloaderGeneratorMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Management\AutoloaderGenerator');
    }
}
