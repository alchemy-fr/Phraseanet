<?php

namespace Alchemy\Tests\Phrasea\Plugin\Importer;

use Alchemy\Phrasea\Plugin\Importer\Importer;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

class ImporterTest extends PluginTestCase
{
    public function testImport()
    {
        $source = 'here';
        $target = 'there';

        $strategy = $this->getMock('Alchemy\Phrasea\Plugin\Importer\ImportStrategy');
        $strategy->expects($this->once())
            ->method('detect')
            ->with($source)
            ->will($this->returnValue('elephant'));

        $importerInterface = $this->getMock('Alchemy\Phrasea\Plugin\Importer\ImporterInterface');
        $importerInterface->expects($this->once())
            ->method('import')
            ->with($source, $target);

        $importer = new Importer($strategy, ['elephant' => $importerInterface]);
        $importer->import($source, $target);
    }

    /**
     * @expectedException \Alchemy\Phrasea\Plugin\Exception\ImportFailureException
     */
    public function testImportFailure()
    {
        $source = 'here';
        $target = 'there';

        $strategy = $this->getMock('Alchemy\Phrasea\Plugin\Importer\ImportStrategy');
        $strategy->expects($this->once())
            ->method('detect')
            ->with($source)
            ->will($this->returnValue('elephant'));

        $importerInterface = $this->getMock('Alchemy\Phrasea\Plugin\Importer\ImporterInterface');
        $importerInterface->expects($this->never())
            ->method('import');

        $importer = new Importer($strategy, ['rhinoceros' => $importerInterface]);
        $importer->import($source, $target);
    }
}
