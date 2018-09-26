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
use Alchemy\Phrasea\ControllerProvider\Api\Api;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Alchemy\Phrasea\Report\Controller\ApiReportController;
use Alchemy\Phrasea\Report\ReportFactory;
use Alchemy\Phrasea\Report\ReportService;
use Silex\Application;
use Silex\Controller;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


class ApiReportControllerProvider extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;


    const VERSION = '2.0.0';

    public function register(Application $app)
    {
        $app['controller.api.v2.report'] = $app->share(
            function (PhraseaApplication $app) {
                return (new ApiReportController(
                    $app['report.factory'],
                    $app['report.service'],
                    $app['conf']->get(['registry', 'modules', 'anonymous-report']),
                    $app->getAclForUser($app->getAuthenticatedUser())
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

        $app['report.service'] = $app->share(
            function (PhraseaApplication $app) {
                return (new ReportService(
                    $app['conf']->get(['main', 'key']),
                    $app['phraseanet.appbox'],
                    $app->getAclForUser($app->getAuthenticatedUser())
                ));
            }
        );
    }

    public function boot(Application $app)
    {
        // Intentionally left empty
    }

    public function connect(Application $app)
    {
        if (! $this->isApiEnabled($app)) {
            return $app['controllers_factory'];
        }

        $controllers = $this->createCollection($app);
        /*
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAccessToModule('report');
        });
        */

        $controllers->before(new OAuthListener());
        /*
        if ($request->attributes->has('basket')) {
            if (!$app['acl.basket']->hasAccess($request->attributes->get('basket'), $app->getAuthenticatedUser())) {
                throw new AccessDeniedHttpException('Current user does not have access to the basket');
            }
        }
        */
        $controllers
            ->get('/', 'controller.api.v2.report:rootAction')
            // ->bind('api_v2_report_root');
        ;

        $controllers
            ->get('/connections/{sbasId}/', 'controller.api.v2.report:connectionsAction')
            ->assert('sbasId', '\d+')
        ;

        $controllers
            ->get('/downloads/{sbasId}/', 'controller.api.v2.report:downloadsAction')
            ->assert('sbasId', '\d+')
        ;

        $controllers
            ->get('/records/{sbasId}/', 'controller.api.v2.report:recordsAction')
            ->assert('sbasId', '\d+')
        ;

        return $controllers;
    }
    /*
    private function addReportMiddleware(Application $app, Controller $controller)
    {
        // $controller
        //     ->before($app['middleware.report.converter'])
        //     ->before($app['middleware.report.user-access']);

        return $controller;
    }
    */
}
