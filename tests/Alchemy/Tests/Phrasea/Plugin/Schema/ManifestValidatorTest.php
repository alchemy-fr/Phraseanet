<?php

namespace Alchemy\Tests\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use JsonSchema\Validator as JsonSchemaValidator;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

class ManifestValidatorTest extends PluginTestCase
{
    /**
     * @dataProvider provideGoodManifestFiles
     */
    public function testValidateGoodOnes($file)
    {
        $validator = $this->createValidator();
        $validator->validate(json_decode(file_get_contents($file)));
    }

    public function provideGoodManifestFiles()
    {
        return [
            [__DIR__ . '/../Fixtures/manifest-good-big.json'],
            [__DIR__ . '/../Fixtures/manifest-good-minimal.json'],
        ];
    }

    /**
     * @expectedException Alchemy\Phrasea\Plugin\Exception\JsonValidationException
     * @dataProvider provideWrongManifestFiles
     */
    public function testValidateWrongOnes($file)
    {
        $validator = $this->createValidator();
        $validator->validate(json_decode(file_get_contents($file)));
    }

    public function provideWrongManifestFiles()
    {
        return [
            [__DIR__ . '/../Fixtures/manifest-wrong1.json'],
            [__DIR__ . '/../Fixtures/manifest-wrong2.json'],
            [__DIR__ . '/../Fixtures/manifest-wrong3.json'],
            [__DIR__ . '/../Fixtures/manifest-wrong4.json'],
            [__DIR__ . '/../Fixtures/manifest-wrong5-min-version.json'],
            [__DIR__ . '/../Fixtures/manifest-wrong6-max-version.json'],
            [__DIR__ . '/../Fixtures/manifest-wrong7-invalid-name.json']
        ];
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testValidateInvalidData()
    {
        $validator = $this->createValidator();
        $validator->validate([]);
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testConstructWithInvalidSchema()
    {
        new ManifestValidator(new JsonSchemaValidator(), [], self::$DI['cli']['phraseanet.version']);
    }

    public function testCreate()
    {
        $validator = ManifestValidator::create(self::$DI['cli']);

        $this->assertInstanceOf('Alchemy\Phrasea\Plugin\Schema\ManifestValidator', $validator);
    }

    private function createValidator()
    {
        $schema = json_decode($this->getSchema());

        return new ManifestValidator(new JsonSchemaValidator(), $schema, self::$DI['cli']['phraseanet.version']);
    }
}
