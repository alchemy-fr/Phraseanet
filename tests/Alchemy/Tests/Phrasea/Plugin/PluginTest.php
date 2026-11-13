<?php

namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\Plugin;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PluginTest extends PluginTestCase
{
    public function testGetters()
    {
        $manifest = $this->createManifestMock();
        $error = $this->createStub('Alchemy\Phrasea\Plugin\Exception\PluginValidationException');

        $plugin = new Plugin('toto', $manifest, null);
        $this->assertSame('toto', $plugin->getName());
        $this->assertSame($manifest, $plugin->getManifest());
        $this->assertNull($plugin->getError());
        $this->assertFalse($plugin->isErroneous());

        $plugin = new Plugin('toto', null, $error);
        $this->assertSame('toto', $plugin->getName());
        $this->assertNull($plugin->getManifest());
        $this->assertSame($error, $plugin->getError());
        $this->assertTrue($plugin->isErroneous());
    }

    public function testBothNull()
    {
        $this->expectException(\LogicException::class);
        new Plugin('toto', null, null);
    }

    public function testBothNotNull()
    {
        $this->expectException(\LogicException::class);
        $manifest = $this->createManifestMock();
        $error = $this->createStub('Alchemy\Phrasea\Plugin\Exception\PluginValidationException');

        new Plugin('toto', $manifest, $error);
    }
}
