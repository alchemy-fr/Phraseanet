<?php

namespace Alchemy\Tests\Phrasea\Http;

use Alchemy\Phrasea\Http\XsendfileMapping;

class XsendfileMappingTest extends \PhraseanetWebTestCaseAbstract
{
    public function testOneMapping()
    {
        $dir = __DIR__ . '/../../../../files/';
        $mapping = new XsendfileMapping(array(
            array(
                'directory' => $dir,
                'mount-point' => '/protected/'
            )
        ));

        $this->assertEquals('/protected='.realpath($dir), (string) $mapping);
    }

    public function testMultiMapping()
    {
        $protected = __DIR__ . '/../../../../files/';
        $upload = __DIR__ . '/../../../../';
        $mapping = new XsendfileMapping(array(
            array(
                'directory' => $protected,
                'mount-point' => '/protected/'
            ),
            array(
                'directory' => $upload,
                'mount-point' => '/uploads/'
            ),
        ));

        $this->assertEquals('/protected='.realpath($protected).',/uploads='.realpath($upload), (string) $mapping);
    }

    public function testMultiMappingWithANotExsistingDir()
    {
        $protected = __DIR__ . '/../../../../files/';
        $mapping = new XsendfileMapping(array(
            array(
                'directory' => $protected,
                'mount-point' => '/protected/'
            ),
            array(
                'directory' => '/path/to/nonexistent/directory',
                'mount-point' => '/test/'
            ),
        ));

        $this->assertEquals('/protected='.realpath($protected), (string) $mapping);
    }

    public function testEmptyMapping()
    {
        $mapping = new XsendfileMapping(array());

        $this->assertEquals('', (string) $mapping);
    }
}
