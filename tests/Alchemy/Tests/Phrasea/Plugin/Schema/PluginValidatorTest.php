<?php

namespace Alchemy\Tests\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use JsonSchema\Validator as JsonValidator;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

class PluginValidatorTest extends PluginTestCase
{
    /**
     * @dataProvider provideInvalidPluginDirs
     * @expectedException Alchemy\Phrasea\Plugin\Exception\PluginValidationException
     */
    public function testValidateInvalidPlugin($directory)
    {
        $schema = json_decode($this->getSchema());

        $validator = new PluginValidator(new ManifestValidator(new JsonValidator(), $schema));
        $validator->validatePlugin($directory);
    }

    /**
     * @dataProvider providePluginDirs
     */
    public function testValidatePlugin($directory)
    {
        $schema = json_decode($this->getSchema());

        $validator = new PluginValidator(new ManifestValidator(new JsonValidator(), $schema));
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
            array(__DIR__ . '/../Fixtures/WrongPlugins/TestPluginWrongManifest'),
        );
    }
}
