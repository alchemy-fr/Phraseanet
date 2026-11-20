<?php

/*
 * This file is part of PHP-XPDF.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XPDF;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class XPDFServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['xpdf.configuration'] = array();
        $app['xpdf.default.configuration'] = array(
            'pdftotext.timeout'  => 60,
            'pdftotext.binaries' => 'pdftotext',
        );
        $app['xpdf.logger'] = null;

        $app['xpdf.configuration.build'] = function (Container $app) {
            return array_replace($app['xpdf.default.configuration'], $app['xpdf.configuration']);
        };

        $app['xpdf.pdftotext'] = function (Container $app) {
            $configuration = $app['xpdf.configuration.build'];

            if (isset($configuration['pdftotext.timeout'])) {
                $configuration['timeout'] = $configuration['pdftotext.timeout'];
            }

            return PdfToText::create($configuration, $app['xpdf.logger']);
        };
    }
}
