<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;
use Goodby\CSV\Import\Standard\Lexer;
use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\LexerConfig;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CSVServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['csv.exporter.config'] = $app->share(function () {
            $config = new ExporterConfig();

            return $config
                ->setDelimiter(";")
                ->setEnclosure('"')
                ->setEscape("\\")
                ->setToCharset('UTF-8')
                ->setFromCharset('UTF-8');

        });

        $app['csv.exporter'] = $app->share(function ($app) {
            return new Exporter($app['csv.exporter.config']);
        });

        $app['csv.lexer.config'] = $app->share(function ($app) {
            $lexer = new LexerConfig();
            $lexer->setDelimiter(';')
                ->setEnclosure('"')
                ->setEscape("\\")
                ->setToCharset('UTF-8')
                ->setFromCharset('UTF-8');

            return $lexer;
        });

        $app['csv.lexer'] = $app->share(function ($app) {
            return new Lexer($app['csv.lexer.config']);
        });

        $app['csv.interpreter'] = $app->share(function ($app) {
            return new Interpreter();
        });

        $app['csv.response'] = $app->protect(function ($callback) use ($app) {
            // set headers to fix ie issues
            $response =  new StreamedResponse($callback, 200,  [
                'Expires'               => 'Mon, 26 Jul 1997 05:00:00 GMT',
                'Last-Modified'         => gmdate('D, d M Y H:i:s'). ' GMT',
                'Cache-Control'         => 'no-store, no-cache, must-revalidate',
                'Cache-Control'         => 'post-check=0, pre-check=0',
                'Pragma'                => 'no-cache',
                'Content-Type'          => 'text/csv',
                'Cache-Control'         => 'max-age=3600, must-revalidate',
                'Content-Disposition'   => 'max-age=3600, must-revalidate',
            ]);

            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'export.csv'
            ));
        });
    }

    public function boot(Application $app)
    {
    }
}
