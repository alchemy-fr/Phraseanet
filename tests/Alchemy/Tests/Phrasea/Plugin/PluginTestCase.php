<?php

namespace Alchemy\Tests\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;

/**
 * @group functional
 * @group legacy
 */
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
        return $this->getMock('Alchemy\Phrasea\Filesystem\PhraseanetFilesystem');
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
        return $this->getMockBuilder('Alchemy\Phrasea\Plugin\Schema\Manifest')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
