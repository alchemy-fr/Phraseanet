<?php

namespace Alchemy\Tests\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PluginValidatorTest extends PluginTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidPluginDirs')]
    public function testValidateInvalidPlugin($directory)
    {
        $this->expectException(\Alchemy\Phrasea\Plugin\Exception\PluginValidationException::class);
        $validator = new PluginValidator($this->createManifestValidator());
        $validator->validatePlugin($directory);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providePluginDirs')]
    public function testValidatePlugin($directory)
    {
        $validator = new PluginValidator($this->createManifestValidator());
        $validator->validatePlugin($directory);
    }

    public static function providePluginDirs()
    {
        return [
            [__DIR__ . '/../Fixtures/PluginDir/TestPlugin'],
        ];
    }

    public static function provideInvalidPluginDirs()
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
