<?php

namespace XPDF\Tests;

use PHPUnit\Framework\TestCase;
use XPDF\Exception\BinaryNotFoundException;
use XPDF\Exception\InvalidArgumentException;
use XPDF\Exception\RuntimeException;
use XPDF\PdfToText;
use Symfony\Component\Process\ExecutableFinder;

class PdfToTextTest extends TestCase
{
    public function testSetOuputEncoding()
    {
        $pdfToText = $this->createPdfToText();

        $this->assertEquals('UTF-8', $pdfToText->getOuputEncoding());
        $pdfToText->setOuputEncoding('ascii');
        $this->assertEquals('ascii', $pdfToText->getOuputEncoding());
    }

    public function testBinaryNotFound()
    {
        $this->expectException(BinaryNotFoundException::class);
        PdfToText::create(array('pdftotext.binaries' => '/path/to/nowhere'));
    }

    public function testGetTextInvalidFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $pdfToText = $this->createPdfToText();
        $pdfToText->getText('/path/to/nowhere');
    }

    public function testGetText()
    {
        $text = 'This is an UTF-8 encoded string : « Un éléphant ça trompe énormément ! »
It tells about elephant\'s noze !
';
        $pdfToText = $this->createPdfToText();
        $this->assertEquals($text, $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf'));
        $this->assertEquals($text, $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf', 1, 1));

        // Poppler pdftotext fails on out of range pages where xpdf returns an empty text
        try {
            $this->assertEquals('', $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf', 2, 2));
        } catch (RuntimeException $e) {
        }
    }

    public function testGetTextWithPageQuantity()
    {
        $text = 'This is an UTF-8 encoded string : « Un éléphant ça trompe énormément ! »
It tells about elephant\'s noze !
';
        $pdfToText = $this->createPdfToText();
        $pdfToText->setPageQuantity(1);
        $this->assertEquals($text, $pdfToText->getText(__DIR__ . '/../../files/HelloWorld.pdf'));
    }

    public function testInvalidPageQuantity()
    {
        $this->expectException(InvalidArgumentException::class);
        $pdfToText = $this->createPdfToText();
        $pdfToText->setPageQuantity(0);
    }

    public function testCreate()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find PHP binary, required for this test');
        }

        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $pdfToText = PdfToText::create(array('pdftotext.binaries' => $php, 'timeout' => 42), $logger);
        $this->assertInstanceOf('XPDF\PdfToText', $pdfToText);
        $this->assertEquals(42, $pdfToText->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals($logger, $pdfToText->getProcessRunner()->getLogger());
        $this->assertEquals($php, $pdfToText->getProcessBuilderFactory()->getBinary());
    }

    private function createPdfToText()
    {
        try {
            return PdfToText::create();
        } catch (BinaryNotFoundException $e) {
            $this->markTestSkipped('pdftotext binary not available');
        }
    }
}
