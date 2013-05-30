<?php

namespace Alchemy\Tests\Phrasea\Plugin;

class PluginTestCase extends \PHPUnit_Framework_TestCase
{
    protected function getPluginDirectory()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'PluginFolder';
    }

    protected function createFilesystemMock()
    {
        return $this->getMock('Symfony\Component\Filesystem\Filesystem');
    }

    protected function getSchema()
    {
        return file_get_contents($this->getSchemaPath());
    }

    protected function getSchemaPath()
    {
        return __DIR__ . '/../../../../../lib/conf.d/plugin-schema.json';
    }
}
