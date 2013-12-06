<?php

namespace Alchemy\Tests\Phrasea\Plugin\Importer;

use Alchemy\Phrasea\Plugin\Importer\ImportStrategy;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

class ImportStrategyTest extends PluginTestCase
{
    /**
     * @dataProvider provideFolderSources
     */
    public function testDetect($source)
    {
        $importer = new ImportStrategy();
        $this->assertEquals('plugins.importer.folder-importer', $importer->detect($source));
    }

    /**
     * @dataProvider provideInvalidFolderSources
     * @expectedException \Alchemy\Phrasea\Plugin\Exception\ImportFailureException
     */
    public function testDetectFailure($source)
    {
        $importer = new ImportStrategy();
        $importer->detect($source);
    }

    public function provideFolderSources()
    {
        return [
            [__DIR__],
            [dirname(__DIR__)],
        ];
    }

    public function provideInvalidFolderSources()
    {
        return [
            ['/path/to/invalid/dir'],
            [__FILE__],
        ];
    }
}
