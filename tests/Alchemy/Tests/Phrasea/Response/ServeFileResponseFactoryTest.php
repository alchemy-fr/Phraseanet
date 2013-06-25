<?php

namespace Alchemy\Tests\Phrasea\Response;

use Alchemy\Phrasea\Response\ServeFileResponseFactory;

class ServeFileResponseFactoryTest extends \PhraseanetWebTestCaseAbstract
{
    protected $factory;

    public function testDeliverFile()
    {
        $this->factory = new ServeFileResponseFactory(
            false,
            array(
            __DIR__ . '/../../../../files/' => '/protected/'
        ), new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('inline; filename="cestlafete.jpg"', $response->headers->get('content-disposition'));
    }

    public function testDeliverFileWithFilename()
    {
        $this->factory = new ServeFileResponseFactory(
            false,
            array(
            __DIR__ . '/../../../../files/' => '/protected/'
        ), new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('inline; filename="toto.jpg"', $response->headers->get('content-disposition'));
    }

    public function testDeliverFileWithFilenameAndDisposition()
    {
        $this->factory = new ServeFileResponseFactory(
            false,
            array(
            __DIR__ . '/../../../../files/' => '/protected/'
        ), new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFile()
    {
        $this->factory = new ServeFileResponseFactory(
            true,
            array(
            __DIR__ . '/../../../../files/' => '/protected/'
        ), new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('/protected/cestlafete.jpg', $response->headers->get('x-accel-redirect'));
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFileAndNoTrailingSlashes()
    {
        $this->factory = new ServeFileResponseFactory(
            true,
            array(
            __DIR__ . '/../../../../files' => 'protected'
        ), new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../files/cestlafete.jpg', 'toto.jpg', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="toto.jpg"', $response->headers->get('content-disposition'));
        $this->assertEquals('/protected/cestlafete.jpg', $response->headers->get('x-accel-redirect'));
    }

    /**
     * @expectedException Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
     */
    public function testDeliverUnexistingFile()
    {
        $this->factory = new ServeFileResponseFactory(
            true,
            array(
            __DIR__ . '/../../../../files' => 'protected'
        ), new \unicode());

        $this->factory->deliverFile(__DIR__ . '/../../../../files/does_not_exists.jpg', 'toto.jpg', 'attachment');
    }

    public function testDeliverFileWithFilenameAndDispositionAndXSendFileButFileNotInXAccelMapping()
    {
        $this->factory = new ServeFileResponseFactory(
            true,
            array(
            __DIR__ . '/../../../../files' => 'protected'
        ), new \unicode());

        $response = $this->factory->deliverFile(__DIR__ . '/../../../../classes/PhraseanetPHPUnitAbstract.php', 'PhraseanetPHPUnitAbstract.php', 'attachment');

        $this->assertInstanceOf("Symfony\Component\HttpFoundation\Response", $response);
        $this->assertEquals('attachment; filename="PhraseanetPHPUnitAbstract.php"', $response->headers->get('content-disposition'));
        $this->assertEquals(null, $response->headers->get('x-accel-redirect'));
    }

    public function testDeliverDatas()
    {
        $this->factory = new ServeFileResponseFactory(
            false,
            array(
            __DIR__ . '/../../../../files/' => '/protected/'
        ), new \unicode());

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
