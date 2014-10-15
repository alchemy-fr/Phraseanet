<?php

namespace Alchemy\Tests\Phrasea\Http\StaticFile;

use Alchemy\Phrasea\Http\StaticFile\Apache;
use Symfony\Component\HttpFoundation\Request;

class ApacheModeTest extends \PhraseanetWebTestCase
{
    public function testGetVirtualHost()
    {
        $mode = new Apache(array(
            'directory' => __DIR__,
            'mount-point' => '/thumbs'
        ), self::$DI['app']['phraseanet.thumb-symlinker']);
        $conf = $mode->getVirtualHostConfiguration();
        $this->assertRegExp('#'.__DIR__ . '#', $conf);
    }

    /**
     * @dataProvider provideMappings
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testInvalidMapping($mapping)
    {
        new Apache($mapping, self::$DI['app']['phraseanet.thumb-symlinker']);
    }

    public function provideMappings()
    {
        return array(
            array(array('Directory' => __DIR__)),
            array(array('wrong-key' => __DIR__, 'mount-point' => '/')),
            array(array('directory' => __DIR__, 'wrong-key' => '/')),
        );
    }
}
