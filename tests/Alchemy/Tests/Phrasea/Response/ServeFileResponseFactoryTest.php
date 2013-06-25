<?php

namespace Alchemy\Tests\Phrasea\Response;

use Alchemy\Phrasea\Response\ServeFileResponseFactory;

class ServeFileResponseFactoryTest extends \PhraseanetWebTestCaseAbstract
{
    protected $factory;

    public function setup()
    {
        parent::setup();

        $this->factory = new ServeFileResponseFactory(self::$DI['app']['root.path']);
    }

    public function testDeliverFile()
    {
        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg');
        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('inline; filename="cestlafete.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('public', $response->headers->get('pragma'));
    }

    public function testDeliverFileWithFilename()
    {
        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg');
        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('inline; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('public', $response->headers->get('pragma'));
    }

    public function testDeliverFileWithFilenameAndDisposition()
    {
        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');
        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('public', $response->headers->get('pragma'));
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFile()
    {
        ServeFileResponseFactory::$X_SEND_FILE = true;
        $this->factory->setXAccelRedirectMountPoint('protected');
        $this->factory->setXAccelRedirectPath(__DIR__ . '/../../../../files');

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('public', $response->headers->get('pragma'));
        $this->assertEquals('/protected/cestlafete.jpg', $response->headers->get('x-accel-redirect'));
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFileAndTrailingSlashes()
    {
        ServeFileResponseFactory::$X_SEND_FILE = true;
        $this->factory->setXAccelRedirectMountPoint('/protected/');
        $this->factory->setXAccelRedirectPath(__DIR__ . '/../../../../files/');

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('public', $response->headers->get('pragma'));
        $this->assertEquals('/protected/cestlafete.jpg', $response->headers->get('x-accel-redirect'));
    }

    public function testDeliverDatas()
    {
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
