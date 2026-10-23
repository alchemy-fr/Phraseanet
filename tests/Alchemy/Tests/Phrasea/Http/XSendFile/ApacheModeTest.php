<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\XSendFile\ApacheMode;
use Symfony\Component\HttpFoundation\Request;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ApacheModeTest extends \PhraseanetTestCase
{
    public function testGetVirtualHost()
    {
        $mode = new ApacheMode([['directory' => __DIR__ ]]);
        $conf = $mode->getVirtualHostConfiguration();
        $this->assertMatchesRegularExpression('#'.__DIR__ . '#', $conf);
    }

    public function testSetValidHeaders()
    {
        $request = Request::create('/');
        $mode = new ApacheMode([['directory' => __DIR__ ]]);
        $mode->setHeaders($request);
        $this->assertArrayHasKey('x-sendfile-type', $request->headers->all());
    }

    public function testUnextingDirectoryMapping()
    {
        $mode = new ApacheMode([['directory' => __DIR__ . '/Unknown/Dir']]);
        $this->assertEquals([], $mode->getMapping());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideMappings')]
    public function testInvalidMapping($mapping)
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        new ApacheMode($mapping);
    }

    public static function provideMappings()
    {
        return [
            [[['wrong-key' => __DIR__]]],
            [['not-an-array']],
        ];
    }
}
