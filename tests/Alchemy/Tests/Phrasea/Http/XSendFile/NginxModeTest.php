<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\XSendFile\NginxMode;
use Symfony\Component\HttpFoundation\Request;

class NginxModeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetVirtualHost()
    {
        $mode = new NginxMode([[
            'directory' => __DIR__, 'mount-point' => '/download']]);
        $conf = $mode->getVirtualHostConfiguration();
        $this->assertRegExp('#'.__DIR__ . '#', $conf);
    }

    public function testSetValidHeaders()
    {
        $request = Request::create('/');
        $mode = new NginxMode([['directory' => __DIR__, 'mount-point' => '/download']]);
        $mode->setHeaders($request);
        $this->assertArrayHasKey('x-sendfile-type', $request->headers->all());
        $this->assertArrayHasKey('x-accel-mapping', $request->headers->all());
    }

    public function testSetValidMultiHeaders()
    {
        $protected = __DIR__ . '/../../../../../files/';
        $upload = __DIR__ . '/../../../../../';

        $request = Request::create('/');
        $mode = new NginxMode([
            [
                'directory' => $protected,
                'mount-point' => '/protected/'
            ],
            [
                'directory' => $upload,
                'mount-point' => '/uploads/'
            ],
        ]);
        $mode->setHeaders($request);
        $this->assertArrayHasKey('x-sendfile-type', $request->headers->all());
        $this->assertArrayHasKey('x-accel-mapping', $request->headers->all());
        $this->assertEquals(realpath($protected).'=/protected,'.realpath($upload).'=/uploads', $request->headers->get('X-Accel-Mapping'));
    }

    public function testSetInvalidHeaders()
    {
        $request = Request::create('/');
        $mode = new NginxMode([['directory' => __DIR__ . '/Unknown/Dir', 'mount-point' => '/download']]);
        $mode->setHeaders($request);
        $this->assertArrayNotHasKey('x-sendfile-type', $request->headers->all());
        $this->assertArrayNotHasKey('x-accel-mapping', $request->headers->all());
    }

    public function testUnextingDirectoryMapping()
    {
        $mode = new NginxMode([['directory' => __DIR__ . '/Unknown/Dir', 'mount-point' => '/download']]);
        $this->assertEquals([], $mode->getMapping());
    }

    /**
     * @dataProvider provideMappings
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testInvalidMapping($mapping)
    {
        new NginxMode($mapping);
    }

    public function provideMappings()
    {
        return [
            [[['Directory' => __DIR__]]],
            [[['wrong-key' => __DIR__, 'mount-point' => '/']]],
            [[['directory' => __DIR__, 'wrong-key' => '/']]],
            [['not-an-array']],
        ];
    }
}
