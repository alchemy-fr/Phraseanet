<?php

namespace Alchemy\Tests\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Schema\Manifest;

class ManifestTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/../Fixtures/PluginDir/TestPlugin/manifest.json'), true);
        $manifest = new Manifest($data);

        $this->assertEquals('TestPlugin', $manifest->getName());
        $this->assertEquals('A custom class connector', $manifest->getDescription());
        $this->assertEquals(array('connector'), $manifest->getKeywords());
        $this->assertEquals(array(array(
            'name' => 'Author name',
            'homepage' => 'http://example.com',
            'email' => 'email@example.com',
        )), $manifest->getAuthors());
        $this->assertEquals('http://example.com/project/example', $manifest->getHomepage());
        $this->assertEquals('MIT', $manifest->getLicense());
        $this->assertEquals('0.1', $manifest->getVersion());
        $this->assertEquals('3.8', $manifest->getMinimumPhraseanetVersion());
        $this->assertEquals('3.9', $manifest->getMaximumPhraseanetVersion());
        $this->assertEquals(array('views', 'twig-views'), $manifest->getTwigPaths());
        $this->assertEquals(array(array('class' => 'Vendor\CustomCommand')), $manifest->getCommands());
        $this->assertEquals(array(array('class' => 'Vendor\PluginService')), $manifest->getServices());
        $this->assertEquals(array('property' => 'value'), $manifest->getExtra());
    }
}
