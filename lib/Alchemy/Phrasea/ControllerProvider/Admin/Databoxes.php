<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Admin;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Admin\DataboxesController;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Databoxes implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controller.admin.databoxes'] = $app->share(function (PhraseaApplication $app) {
            return new DataboxesController($app);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        /** @var Firewall $firewall */
        $firewall = $app['firewall'];
        $firewall->addMandatoryAuthentication($controllers);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAccessToModule('admin');
        });

        $controllers->get('/', 'controller.admin.databoxes:getDatabases')
            ->bind('admin_databases');

        $controllers->post('/', 'controller.admin.databoxes:createDatabase')
            ->bind('admin_database_new')
            ->before(function () use ($firewall) {
                $firewall->requireAdmin();
            });

        $controllers->post('/mount/', 'controller.admin.databoxes:databaseMount')
            ->bind('admin_database_mount')
            ->before(function () use ($firewall) {
                $firewall->requireAdmin();
            });

        return $controllers;
    }
}
