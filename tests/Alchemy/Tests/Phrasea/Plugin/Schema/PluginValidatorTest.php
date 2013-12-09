<?php

namespace Alchemy\Tests\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

class PluginValidatorTest extends PluginTestCase
{
    /**
     * @dataProvider provideInvalidPluginDirs
     * @expectedException Alchemy\Phrasea\Plugin\Exception\PluginValidationException
     */
    public function testValidateInvalidPlugin($directory)
    {
        $validator = new PluginValidator($this->createManifestValidator());
        $validator->validatePlugin($directory);
    }

    /**
     * @dataProvider providePluginDirs
     */
    public function testValidatePlugin($directory)
    {
        $validator = new PluginValidator($this->createManifestValidator());
        $validator->validatePlugin($directory);
    }

    public function providePluginDirs()
    {
        return [
            [__DIR__ . '/../Fixtures/PluginDir/TestPlugin'],
        ];
    }

    public function provideInvalidPluginDirs()
    {
        return [
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidManifest'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginMissingComposer'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginMissingManifest'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidName'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidTwigPath'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginNoPublicDirectory'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidTwigPathMapping'],
            [__DIR__ . '/../Fixtures/WrongPlugins/TestPluginWrongManifest'],
        ];
    }
}
