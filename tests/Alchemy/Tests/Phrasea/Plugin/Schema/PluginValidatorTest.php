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
        return array(
            array(__DIR__ . '/../Fixtures/PluginDir/TestPlugin'),
        );
    }

    public function provideInvalidPluginDirs()
    {
        return array(
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidManifest'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginMissingComposer'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginMissingManifest'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidName'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidTwigPath'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginNoPublicDirectory'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginInvalidTwigPathMapping'),
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginWrongManifest'),
        );
    }
}
