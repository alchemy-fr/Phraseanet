<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report\ControllerProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Report\Controller\ProdReportController;
use Alchemy\Phrasea\Report\ReportFactory;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


class ProdReportControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.report'] = $app->share(
            function (PhraseaApplication $app) {
                return (new ProdReportController(
                    $app['report.factory'],
                    $app['conf']->get(['registry', 'modules', 'anonymous-report']),
                    $app->getAclForUser($app->getAuthenticatedUser()),
                    $app['phraseanet.appbox']
                ));
            }
        );

        $app['report.factory'] = $app->share(
            function (PhraseaApplication $app) {
                return (new ReportFactory(
                    $app['conf']->get(['main', 'key']),
                    $app['phraseanet.appbox'],
                    $app->getAclForUser($app->getAuthenticatedUser())
                ));
            }
        );
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
        
        $controllers
            ->match('/connections/{sbasId}/', 'controller.prod.report:connectionsAction')
            ->assert('sbasId', '\d+')
            ->bind('report2_connections')
            ->method('GET|POST')
        ;

        $controllers
            ->match('/downloads/{sbasId}/', 'controller.prod.report:downloadsAction')
            ->assert('sbasId', '\d+')
            ->bind('report2_downloads')
            ->method('GET|POST')
        ;

        $controllers
            ->match('/records/{sbasId}/', 'controller.prod.report:recordsAction')
            ->assert('sbasId', '\d+')
            ->bind('report2_records')
            ->method('GET|POST')
        ;

        return $controllers;
    }
}
