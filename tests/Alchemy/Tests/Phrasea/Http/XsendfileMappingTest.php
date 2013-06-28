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

    /**
     * @dataProvider provideVariousMappings
     */
    public function testGetMapping($map, $expected)
    {
        $mapping = new XsendfileMapping($map);

        $this->assertEquals($expected, $mapping->getMapping());
    }

    public function provideVariousMappings()
    {
        return array(
            array(array(), array()),
            array(array(array('mount-point' => false, 'directory' => false)), array()),
            array(array(array('mount-point' => 'mount', 'directory' => false)), array()),
            array(array(array('mount-point' => false, 'directory' => __DIR__)), array()),
            array(array(array('mount-point' => 'mount', 'directory' => __DIR__)), array(array('mount-point' => '/mount', 'directory' => __DIR__))),
            array(array(array('mount-point' => '/mount/', 'directory' => __DIR__ . '/../..')), array(array('mount-point' => '/mount', 'directory' => realpath(__DIR__.'/../..')))),
        );
    }

    /**
     * @dataProvider provideInvalidMappings
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testInvalidMapping($map)
    {
        new XsendfileMapping($map);
    }

    public function provideInvalidMappings()
    {
        return array(
            array(array('mount-point' => '/mount', 'directory' => __DIR__)),
            array(array(array('mount-point' => '/mount'))),
            array(array(array('directory' => __DIR__))),
        );
    }
}
