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
        $controllers
            ->match('/', 'controller.api.v2.report:rootAction')
            ->method('GET|POST')
        ;

        $controllers
            ->match('/connections/{sbasId}/', 'controller.api.v2.report:connectionsAction')
            ->assert('sbasId', '\d+')
            ->method('GET|POST')
        ;

        $controllers
            ->match('/downloads/{sbasId}/', 'controller.api.v2.report:downloadsAction')
            ->assert('sbasId', '\d+')
            ->method('GET|POST')
        ;

        $controllers
            ->match('/records/{sbasId}/', 'controller.api.v2.report:recordsAction')
            ->assert('sbasId', '\d+')
            ->method('GET|POST')
        ;

        return $controllers;
    }
}
