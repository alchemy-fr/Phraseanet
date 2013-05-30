<?php

namespace Alchemy\Tests\Phrasea\Plugin\Importer;

use Alchemy\Phrasea\Plugin\Importer\FolderImporter;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;
use Symfony\Component\Filesystem\Exception\IOException;

class FolderImporterTest extends PluginTestCase
{
    public function testImport()
    {
        $fs = $this->createFilesystemMock();

        $source = 'test-plugin';
        $target = __DIR__;

        $fs->expects($this->once())
            ->method('mirror')
            ->with($source, $target);

        $importer = new FolderImporter($fs);
        $importer->import($source, $target);
    }

    /**
     * @expectedException Alchemy\Phrasea\Plugin\Exception\ImportFailureException
     */
    public function testImportFailed()
    {
        $fs = $this->createFilesystemMock();

        $source = 'test-plugin';
        $target = __DIR__;

        $fs->expects($this->once())
            ->method('mirror')
            ->with($source, $target)
            ->will($this->throwException(new IOException('Error')));

        $importer = new FolderImporter($fs);
        $importer->import($source, $target);
    }
}
