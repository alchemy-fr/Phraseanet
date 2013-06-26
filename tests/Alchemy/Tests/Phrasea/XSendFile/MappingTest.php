<?php

namespace Alchemy\Tests\Phrasea\XSendFile;

use Alchemy\Phrasea\XSendFile\Mapping;

class MappingTest extends \PhraseanetWebTestCaseAbstract
{
    public function testOneMapping()
    {
        $mapping = new Mapping(array(
            array(
                'directory' => __DIR__ . '/../../../../files/',
                'mount-point' => '/protected/'
            )
        ));

        $this->assertEquals('/protected/=/home/nlegoff/workspace/Phraseanet/tests/files/', (string) $mapping);
    }

    public function testMultiMapping()
    {
         $mapping = new Mapping(array(
            array(
                'directory' => __DIR__ . '/../../../../files/',
                'mount-point' => '/protected/'
            ),
            array(
                'directory' => __DIR__ . '/../../../../',
                'mount-point' => '/uploads/'
            ),
        ));

        $this->assertEquals('/protected/=/home/nlegoff/workspace/Phraseanet/tests/files/,/uploads/=/home/nlegoff/workspace/Phraseanet/tests/', (string) $mapping);
    }

    public function testMultiMappingWithANotExsistingDir()
    {
         $mapping = new Mapping(array(
            array(
                'directory' => __DIR__ . '/../../../../files/',
                'mount-point' => '/protected/'
            ),
            array(
                'directory' => __DIR__ . '/../../../../do_not_exists',
                'mount-point' => '/test/'
            ),
        ));

        $this->assertEquals('/protected/=/home/nlegoff/workspace/Phraseanet/tests/files/', (string) $mapping);
    }

    public function testEmptyMapping()
    {
         $mapping = new Mapping(array());

        $this->assertEquals('', (string) $mapping);
    }
}