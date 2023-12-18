<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Root;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Root\DeveloperController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Developers implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.account.developers'] = $app->share(function (PhraseaApplication $app) {
            return (new DeveloperController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireNotGuest();
        });

        $controllers->get('/applications/', 'controller.account.developers:listApps')
            ->bind('developers_applications');

        $controllers->get('/application/new/', 'controller.account.developers:displayFormApp')
            ->bind('developers_application_new');

        $controllers->post('/application/', 'controller.account.developers:newApp')
            ->bind('submit_developers_application');

        $controllers->get('/application/{application}/', 'controller.account.developers:getApp')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('developers_application');

        $controllers->post('/application/{application}/listened-event', 'controller.account.developers:updateListenedEvent')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('developers_application_listened_event');

        $controllers->post('/application/{application}/active-webhook', 'controller.account.developers:activeWebhook')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('developers_application_active_webhook');

        $controllers->delete('/application/{application}/', 'controller.account.developers:deleteApp')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('delete_developers_application');

        $controllers->post('/application/{application}/authorize_grant_password/', 'controller.account.developers:authorizeGrantPassword')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_developers_application_authorize_grant_password');

        $controllers->post('/application/{application}/access_token/', 'controller.account.developers:renewAccessToken')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_developers_application_token');

        $controllers->post('/application/{application}/callback/', 'controller.account.developers:renewAppCallback')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_application_callback');

        $controllers->post('/application/{application}/webhook/', 'controller.account.developers:renewAppWebhook')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_application_webhook');

        return $controllers;
    }
}
