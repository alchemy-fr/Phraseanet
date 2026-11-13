<?php

namespace XPDF\Tests;

use PHPUnit\Framework\TestCase;
use XPDF\Exception\BinaryNotFoundException;
use XPDF\Exception\InvalidArgumentException;
use XPDF\PdfInfo;

class PdfInfoTest extends TestCase
{
    public function testBinaryNotFound()
    {
        $this->expectException(BinaryNotFoundException::class);
        PdfInfo::create(array('pdfinfo.binaries' => '/path/to/nowhere'));
    }

    public function testExtractInfoInvalidFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createPdfInfo()->extractInfo('/path/to/nowhere');
    }

    public function testExtractInfo()
    {
        $pdfInfo = $this->createPdfInfo();
        $data = $pdfInfo->extractInfo(__DIR__ . '/../../files/HelloWorld.pdf');

        $this->assertArrayHasKey('Pages', $data);
        $this->assertEquals('1', $data['Pages']);
        $this->assertEquals($data, $pdfInfo->getData());
    }

    private function createPdfInfo()
    {
        try {
            return PdfInfo::create();
        } catch (BinaryNotFoundException $e) {
            $this->markTestSkipped('pdfinfo binary not available');
        }
    }
}
