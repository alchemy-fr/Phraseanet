<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;
use Silex\Application;
use Symfony\Component\Process\ExecutableFinder;
use XPDF\XPDFServiceProvider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider
 */
class BorderManagerServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider',
                'border-manager',
                'Alchemy\\Phrasea\\Border\\Manager'
            ),
        );
    }

    public function testItLoadsWithoutXPDF()
    {
        $app = new Application();
        $app->register(new XPDFServiceProvider(), array(
            'xpdf.configuration' => array(
                'pdftotext.binaries' => '/path/to/nowhere',
            )
        ));
        $app->register(new BorderManagerServiceProvider());
        $app['phraseanet.configuration'] = array('border-manager' => array('enabled' => false));

        $this->assertInstanceOf('Alchemy\Phrasea\Border\Manager', $app['border-manager']);
        $this->assertNull($app['border-manager']->getPdfToText());
    }

    public function testItLoadsWithXPDF()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find php binary, mandatory for this test');
        }

        $app = new Application();
        $app->register(new XPDFServiceProvider(), array(
            'xpdf.configuration' => array(
                'pdftotext.binaries' => $php,
            )
        ));
        $app->register(new BorderManagerServiceProvider());
        $app['phraseanet.configuration'] = array('border-manager' => array('enabled' => false));

        $this->assertInstanceOf('Alchemy\Phrasea\Border\Manager', $app['border-manager']);
        $this->assertInstanceOf('XPDF\PdfToText', $app['border-manager']->getPdfToText());
    }
}
