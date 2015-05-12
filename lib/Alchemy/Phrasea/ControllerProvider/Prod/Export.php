<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\ExportController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Export implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.export'] = $app->share(function (PhraseaApplication $app) {
            return (new ExportController($app))
                ->setDispatcherLocator(function () use ($app) {
                    return $app['dispatcher'];
                });
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->post('/multi-export/', 'controller.prod.export:displayMultiExport')
            ->bind('export_multi_export');

        $controllers->post('/mail/', 'controller.prod.export:exportMail')
            ->bind('export_mail');

        $controllers->post('/ftp/', 'controller.prod.export:exportFtp')
            ->bind('export_ftp');

        $controllers->post('/ftp/test/', 'controller.prod.export:testFtpConnexion')
            ->bind('export_ftp_test');

        return $controllers;
    }
}
