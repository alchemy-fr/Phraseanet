<?php

namespace Alchemy\Phrasea\PhraseanetService\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\PhraseanetService\Controller\PSAdminController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class PSAdminServiceProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['controller.ps.admin'] = $app->share(function (PhraseaApplication $app) {
            return new PSAdminController($app);
        });
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->match('/',  'controller.ps.admin:indexAction')
            ->method('GET')
            ->bind('ps_admin');

        $controllers->match('/auth', 'controller.ps.admin:authAction')
            ->method('GET|POST')
            ->bind('ps_admin_auth')
        ;

        $controllers->match('/expose', 'controller.ps.admin:exposeAction')
            ->method('GET|POST')
            ->bind('ps_admin_expose')
        ;

        $controllers->match('/uploader', 'controller.ps.admin:uploaderAction')
            ->method('GET|POST')
            ->bind('ps_admin_uploader')
        ;

        $controllers->match('/notify', 'controller.ps.admin:notifyAction')
            ->method('GET|POST')
            ->bind('ps_admin_notify')
        ;

        $controllers->match('/report', 'controller.ps.admin:reportAction')
            ->method('GET|POST')
            ->bind('ps_admin_report')
        ;

        return $controllers;
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {

    }
}
