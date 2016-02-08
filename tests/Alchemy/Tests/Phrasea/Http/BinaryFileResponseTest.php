<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Http;


use Alchemy\Phrasea\Http\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class BinaryFileResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideRanges
     */
    public function testRequestsWithoutEtag($requestRange, $offset, $length, $responseRange)
    {
        $response = BinaryFileResponse::create(__DIR__.'/Fixtures/test.gif', 200, array('Content-Type' => 'application/octet-stream'));

        // do a request to get the LastModified
        $request = Request::create('/');
        $response->prepare($request);
        $lastModified = $response->headers->get('Last-Modified');

        // prepare a request for a range of the testing file
        $request = Request::create('/');
        $request->headers->set('If-Range', $lastModified);
        $request->headers->set('Range', $requestRange);

        $file = fopen(__DIR__.'/Fixtures/test.gif', 'r');
        fseek($file, $offset);
        $data = fread($file, $length);
        fclose($file);

        $this->expectOutputString($data);
        $response = clone $response;
        $response->prepare($request);
        $response->sendContent();

        $this->assertEquals(206, $response->getStatusCode());
        $this->assertEquals($responseRange, $response->headers->get('Content-Range'));
    }

    public function provideRanges()
    {
        return array(
            array('bytes=1-4', 1, 4, 'bytes 1-4/35'),
            array('bytes=-5', 30, 5, 'bytes 30-34/35'),
            array('bytes=30-', 30, 5, 'bytes 30-34/35'),
            array('bytes=30-30', 30, 1, 'bytes 30-30/35'),
            array('bytes=30-34', 30, 5, 'bytes 30-34/35'),
        );
    }

    public function testRangeRequestsWithoutLastModifiedDate()
    {
        // prevent auto last modified
        $response = BinaryFileResponse::create(__DIR__.'/Fixtures/test.gif', 200, array('Content-Type' => 'application/octet-stream'), true, null, false, false);

        // prepare a request for a range of the testing file
        $request = Request::create('/');
        $request->headers->set('If-Range', date('D, d M Y H:i:s').' GMT');
        $request->headers->set('Range', 'bytes=1-4');

        $this->expectOutputString(file_get_contents(__DIR__.'/Fixtures/test.gif'));
        $response = clone $response;
        $response->prepare($request);
        $response->sendContent();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('Content-Range'));
    }
}
