<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\XSendFile\ApacheMode;
use Symfony\Component\HttpFoundation\Request;

class ApacheModeTest extends \PhraseanetPHPUnitAbstract
{
    public function testGetVirtualHost()
    {
        $mode = new ApacheMode(array(array('directory' => __DIR__ )));
        $conf = $mode->getVirtualHostConfiguration();
        $this->assertRegExp('#'.__DIR__ . '#', $conf);
    }

    public function testSetValidHeaders()
    {
        $request = Request::create('/');
        $mode = new ApacheMode(array(array('directory' => __DIR__ )));
        $mode->setHeaders($request);
        $this->assertArrayHasKey('x-sendfile-type', $request->headers->all());
    }

    public function testUnextingDirectoryMapping()
    {
        $mode = new ApacheMode(array(array('directory' => __DIR__ . '/Unknown/Dir')));
        $this->assertEquals(array(), $mode->getMapping());
    }

    /**
     * @dataProvider provideMappings
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testInvalidMapping($mapping)
    {
        new ApacheMode($mapping);
    }

    public function provideMappings()
    {
        return array(
            array(array(array('wrong-key' => __DIR__))),
            array(array('not-an-array')),
        );
    }
}
