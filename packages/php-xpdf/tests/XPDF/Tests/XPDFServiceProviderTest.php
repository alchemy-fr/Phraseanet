<?php

namespace XPDF\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Symfony\Component\Process\ExecutableFinder;
use XPDF\XPDFServiceProvider;

class XPDFServiceProviderTest extends TestCase
{
    public function testInit()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find PHP binary, required for this test');
        }

        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $app = new Container();
        $app->register(new XPDFServiceProvider(), array(
            'xpdf.configuration' => array(
                'pdftotext.timeout'  => 42,
                'pdftotext.binaries' => $php,
            ),
            'xpdf.logger' => $logger,
        ));

        $pdftotext = $app['xpdf.pdftotext'];

        $this->assertInstanceOf('XPDF\PdfToText', $pdftotext);
        $this->assertSame($pdftotext, $app['xpdf.pdftotext']);
        $this->assertEquals(42, $pdftotext->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals($php, $pdftotext->getProcessBuilderFactory()->getBinary());
        $this->assertSame($logger, $pdftotext->getProcessRunner()->getLogger());
    }
}
