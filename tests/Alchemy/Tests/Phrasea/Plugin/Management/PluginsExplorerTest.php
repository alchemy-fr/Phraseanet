<?php

namespace Alchemy\Tests\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Plugin\Management\PluginsExplorer;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

class PluginsExplorerTest extends PluginTestCase
{
    public function testCount()
    {
        $explorer = new PluginsExplorer(__DIR__ . '/../Fixtures/PluginDir');

        $this->assertCount(1, $explorer);
    }

    public function testGetIterator()
    {
        $explorer = new PluginsExplorer(__DIR__ . '/../Fixtures/PluginDir');

        $dirs = [];

        foreach ($explorer as $dir) {
            $dirs[] = (string) realpath($dir);
        }

        $this->assertSame([realpath(__DIR__ . '/../Fixtures/PluginDir/TestPlugin')], $dirs);
    }
}
