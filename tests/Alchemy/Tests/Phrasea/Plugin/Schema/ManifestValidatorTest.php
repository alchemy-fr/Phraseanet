<?php

namespace Alchemy\Tests\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use JsonSchema\Validator as JsonSchemaValidator;
use Alchemy\Tests\Phrasea\Plugin\PluginTestCase;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ManifestValidatorTest extends PluginTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideGoodManifestFiles')]
    public function testValidateGoodOnes($file)
    {
        $validator = $this->createValidator();
        $validator->validate(json_decode(file_get_contents($file)));
    }

    public static function provideGoodManifestFiles()
    {
        return [
            [__DIR__ . '/../Fixtures/manifest-good-big.json'],
            [__DIR__ . '/../Fixtures/manifest-good-minimal.json'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideWrongManifestFiles')]
    public function testValidateWrongOnes($file)
    {
        $this->expectException(\Alchemy\Phrasea\Plugin\Exception\JsonValidationException::class);
        $validator = $this->createValidator();
        $validator->validate(json_decode(file_get_contents($file)));
    }

    public static function provideWrongManifestFiles()
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

    public function testValidateInvalidData()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $validator = $this->createValidator();
        $validator->validate([]);
    }

    public function testConstructWithInvalidSchema()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
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
