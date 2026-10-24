<?php

namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PluginTestCase extends \PhraseanetTestCase
{
    protected function createManifestValidator()
    {
        return ManifestValidator::create(self::$DI['cli']);
    }

    protected function getPluginDirectory()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'PluginFolder';
    }

    protected function createFilesystemMock()
    {
        return $this->createMock('Alchemy\Phrasea\Filesystem\PhraseanetFilesystem');
    }

    protected function getSchema()
    {
        return file_get_contents($this->getSchemaPath());
    }

    protected function getSchemaPath()
    {
        return __DIR__ . '/../../../../../lib/conf.d/plugin-schema.json';
    }

    protected function createManifestMock()
    {
        return $this->createMock('Alchemy\Phrasea\Plugin\Schema\Manifest');
    }
}
