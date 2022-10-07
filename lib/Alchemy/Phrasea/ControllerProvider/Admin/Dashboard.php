<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Admin;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Admin\DashboardController;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Dashboard implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.dashboard'] = $app->share(function (PhraseaApplication $app) {
            return (new DashboardController($app))
                ->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'))
            ;
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAdmin();
        });

        $controllers->get('/', 'controller.admin.dashboard:slash')
            ->bind('admin_dashboard');

        $controllers->post('/flush-cache/', 'controller.admin.dashboard:flush')
            ->bind('admin_dashboard_flush_cache');

        $controllers->post('/flush-session/', 'controller.admin.dashboard:flushSession')
            ->bind('admin_dashboard_flush_session');

        $controllers->post('/send-mail-test/', 'controller.admin.dashboard:sendMail')
            ->bind('admin_dashboard_test_mail');

        $controllers->post('/reset-admin-rights/', 'controller.admin.dashboard:resetAdminRights')
            ->bind('admin_dashboard_reset_admin_rights');

        $controllers->post('/add-admins/', 'controller.admin.dashboard:addAdmins')
            ->bind('admin_dashboard_add_admins');

        return $controllers;
    }
}
