<?php

namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\Plugin;

class PluginTest extends PluginTestCase
{
    public function testGetters()
    {
        $manifest = $this->createManifestMock();
        $error = $this->getMock('Alchemy\Phrasea\Plugin\Exception\PluginValidationException');

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

    /**
     * @expectedException \LogicException
     */
    public function testBothNull()
    {
        new Plugin('toto', null, null);
    }

    /**
     * @expectedException \LogicException
     */
    public function testBothNotNull()
    {
        $manifest = $this->createManifestMock();
        $error = $this->getMock('Alchemy\Phrasea\Plugin\Exception\PluginValidationException');

        new Plugin('toto', $manifest, $error);
    }
}
