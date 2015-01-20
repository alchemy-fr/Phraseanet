<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinkerEncoder;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataReader;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;
use XPDF\Exception\BinaryNotFoundException;

class PhraseanetServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['phraseanet.appbox'] = $app->share(function (SilexApplication $app) {
            return new \appbox($app);
        });

        $app['phraseanet.registry'] = $app->share(function (SilexApplication $app) {
            return new \registry($app);
        });

        $app['firewall'] = $app->share(function (SilexApplication $app) {
            return new Firewall($app);
        });

        $app['events-manager'] = $app->share(function (SilexApplication $app) {
            $events = new \eventsmanager_broker($app);
            $events->start();

            return $events;
        });

        $app['phraseanet.thumb-symlinker'] = $app->share(function (SilexApplication $app) {
            return SymLinker::create($app);
        });

        $app['phraseanet.thumb-symlinker-encoder'] = $app->share(function (SilexApplication $app) {
            return SymLinkerEncoder::create($app);
        });

        $app['phraseanet.metadata-reader'] = $app->share(function (SilexApplication $app) {
            $reader = new PhraseanetMetadataReader();

            try {
                $reader->setPdfToText($app['xpdf.pdftotext']);
            } catch (BinaryNotFoundException $e) {

            }

            return $reader;
        });

        $app['phraseanet.metadata-setter'] = $app->share(function (SilexApplication $app) {
            return new PhraseanetMetadataSetter();
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
