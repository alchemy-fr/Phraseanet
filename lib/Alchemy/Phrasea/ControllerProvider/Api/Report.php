<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Report\Controller\ApiReportController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\Controller;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Report extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    const VERSION = '2.0.0';

    public function register(Application $app)
    {
        $app['controller.api.v2.report'] = $app->share(
            function (PhraseaApplication $app) {
                return (new ApiReportController($app))
                    ->setJsonBodyHelper($app['json.body_helper']);
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
            ->get('/{sbasId}/connections/', 'controller.api.v2.report:connectionsAction')
            ->assert('sbasId', '\d+')
            // ->bind('api_v2_report_root');
        ;

        return $controllers;
    }

    private function addReportMiddleware(Application $app, Controller $controller)
    {
        // $controller
        //     ->before($app['middleware.report.converter'])
        //     ->before($app['middleware.report.user-access']);

        return $controller;
    }
}
