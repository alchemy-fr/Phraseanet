<?php

namespace Alchemy\Tests\Phrasea\Plugin\Importer;

use Alchemy\Phrasea\Plugin\Importer\ImportStrategy;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ImportStrategyTest extends PluginTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideFolderSources')]
    public function testDetect($source)
    {
        $importer = new ImportStrategy();
        $this->assertEquals('plugins.importer.folder-importer', $importer->detect($source));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidFolderSources')]
    public function testDetectFailure($source)
    {
        $this->expectException(\Alchemy\Phrasea\Plugin\Exception\ImportFailureException::class);
        $importer = new ImportStrategy();
        $importer->detect($source);
    }

    public static function provideFolderSources()
    {
        return [
            [__DIR__],
            [dirname(__DIR__)],
        ];
    }

    public static function provideInvalidFolderSources()
    {
        return [
            ['/path/to/invalid/dir'],
            [__FILE__],
        ];
    }
}
