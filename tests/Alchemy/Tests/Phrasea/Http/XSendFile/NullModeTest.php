<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\XSendFile\NullMode;
use Symfony\Component\HttpFoundation\Request;

class NullModeTest extends \PhraseanetTestCase
{
    public function testGetVirtualHost()
    {
        $mode = new NullMode();
        $conf = $mode->getVirtualHostConfiguration();
        $this->assertSame("\n", $conf);
    }

    public function testSetHeaders()
    {
        $mode = new NullMode();
        $request = Request::create('/');
        $before = (string) $request->headers;
        $mode->setHeaders($request);
        $this->assertSame($before, (string) $request->headers);
    }
}
