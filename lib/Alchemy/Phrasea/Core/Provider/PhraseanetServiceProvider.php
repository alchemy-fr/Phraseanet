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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinkerEncoder;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataReader;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application as SilexApplication;
use Pimple\ServiceProviderInterface;
use XPDF\Exception\BinaryNotFoundException;
use Pimple\Container;
use Silex\Api\BootableProviderInterface;

class PhraseanetServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['phraseanet.appbox'] = function (SilexApplication $app) {
            return new \appbox($app);
        };

        $app['firewall'] = function (SilexApplication $app) {
            return new Firewall($app);
        };

        $app['events-manager'] = function (SilexApplication $app) {
            $events = new \eventsmanager_broker($app);
            $events->start();

            return $events;
        };

        $app['phraseanet.thumb-symlinker'] = function (SilexApplication $app) {
            return new SymLinker(
                $app['phraseanet.thumb-symlinker-encoder'],
                $app['filesystem'],
                $app['thumbnail.path']
            );
        };

        $app['phraseanet.thumb-symlinker-encoder'] = function (SilexApplication $app) {
            return new SymLinkerEncoder($app['phraseanet.configuration']['main']['key']);
        };

        $app['acl'] = function (SilexApplication $app) {
            return new ACLProvider($app);
        };

        $app['phraseanet.metadata-reader'] = function (SilexApplication $app) {
            $reader = new PhraseanetMetadataReader();

            try {
                $reader->setPdfToText($app['xpdf.pdftotext']);
            } catch (BinaryNotFoundException $e) {

            }

            return $reader;
        };

        $app['phraseanet.metadata-setter'] = function (Application $app) {
            return new PhraseanetMetadataSetter($app['repo.databoxes'], $app['dispatcher']);
        };

        $app['phraseanet.user-query'] = $app->factory(function (SilexApplication $app) {
            return new \User_Query($app);
        });

        $app['phraseanet.logger'] = $app->protect(function ($databox) use ($app) {
            try {
                return \Session_Logger::load($app, $databox);
            } catch (\Exception_Session_LoggerNotFound $e) {
                return \Session_Logger::create($app, $databox, $app['browser']);
            }
        });

        $app['date-formatter'] = function (SilexApplication $app) {
            return new \phraseadate($app);
        };
    }

    public function boot(SilexApplication $app)
    {
        if ($app['configuration.store']->isSetup()) {
            if (php_sapi_name() == 'cli') {
                $servername = $app['conf']->get('servername');
                if (preg_match('#^http(s)?://#', $servername)) {
                    $t = explode('://', $servername);
                    if (count($t) == 2) {
                        $app['url_generator']->getContext()->setHost($t[1]);
                        $app['url_generator']->getContext()->setScheme($t[0]);
                    }
                } else {
                    $app['url_generator']->getContext()->setHost((string) $servername);
                }
            }
        }
    }
}
