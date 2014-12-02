<?php

namespace Alchemy\Tests\Phrasea\Http;

use Alchemy\Phrasea\Http\ServeFileResponseFactory;
use Alchemy\Phrasea\Http\XSendFile\NginxMode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

class ServeFileResponseFactoryTest extends \PhraseanetWebTestCaseAbstract
{
    protected $factory;

    public function testDeliverFileFactoryCreation()
    {
        $factory = ServeFileResponseFactory::create(self::$DI['app']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\ServeFileResponseFactory', $factory);
    }

    public function testDeliverFile()
    {
        $this->factory = new ServeFileResponseFactory(new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('inline; filename="cestlafete.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals(0, $response->getMaxAge());
        $response->setPrivate();
        $this->assertTrue($response->headers->getCacheControlDirective('private'));
    }

    public function testDeliverFileWithDuration()
    {
        $this->factory = new ServeFileResponseFactory(new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'hello', 'attachment', 'application/json', 23456);

        $this->assertEquals(23456, $response->getMaxAge());
    }

    public function testDeliverFileWithFilename()
    {
        $this->factory = new ServeFileResponseFactory(new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('inline; filename="toto.jpg"', $response->headers->get('content-disposition'));
    }

    public function testDeliverFileWithFilenameAndDisposition()
    {
        $this->factory = new ServeFileResponseFactory(new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFile()
    {
        BinaryFileResponse::trustXSendfileTypeHeader();
        $this->factory = new ServeFileResponseFactory(new \unicode());
        $mode = new NginxMode(
            array(
                array(
                    'directory' => __DIR__ . '/../../../../files/',
                    'mount-point' => '/protected/'
                )
            )
        );
        $request = Request::create('/');
        $mode->setHeaders($request);

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');
        $response->prepare($request);

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('/protected/cestlafete.jpg', $response->headers->get('x-accel-redirect'));
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFileAndNoTrailingSlashes()
    {
        BinaryFileResponse::trustXSendfileTypeHeader();
        $this->factory = new ServeFileResponseFactory(new \unicode());
        $mode = new NginxMode(
            array(
                array(
                    'directory' => __DIR__ . '/../../../../files',
                    'mount-point' => '/protected'
                )
            )
        );
        $request = Request::create('/');
        $mode->setHeaders($request);

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');
        $response->prepare($request);

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('/protected/cestlafete.jpg', $response->headers->get('x-accel-redirect'));
    }

    /**
     * @expectedException Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
     */
    public function testDeliverUnexistingFile()
    {
        BinaryFileResponse::trustXSendfileTypeHeader();
        $this->factory = new ServeFileResponseFactory(new \unicode());

        $this->factory->deliverFile(__DIR__ . '/../../../../files/does_not_exists.jpg', 'toto.jpg', 'attachment');
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFileButFileNotInXAccelMapping()
    {
        BinaryFileResponse::trustXSendfileTypeHeader();
        $this->factory = new ServeFileResponseFactory(new \unicode());
        $mode = new NginxMode(
            array(
                array(
                    'directory' => __DIR__ . '/../../../../files/',
                    'mount-point' => '/protected/'
                )
            )
        );
        $request = Request::create('/');
        $mode->setHeaders($request);

        $file = __DIR__ . '/../../../../classes/PhraseanetPHPUnitAbstract.php';

        $response = $this->factory->deliverFile($file, 'PhraseanetPHPUnitAbstract.php', 'attachment');
        $response->prepare($request);

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="PhraseanetPHPUnitAbstract.php"', $response->headers->get('content-disposition'));
        $this->assertEquals(realpath($file), $response->headers->get('x-accel-redirect'));
    }

    public function testDeliverDatas()
    {
        $this->factory = new ServeFileResponseFactory(new \unicode());

        $data = 'Sex,Name,Birthday
                M,Alphonse,1932
                F,Béatrice,1964
                F,Charlotte,1988';

        $response = $this->factory->deliverData($data, 'data.csv', 'text/csv', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="data.csv"', $response->headers->get('content-disposition'));
        $this->assertEquals('text/csv', $response->headers->get('content-type'));
        $this->assertEquals($data, $response->getContent());
    }
}
