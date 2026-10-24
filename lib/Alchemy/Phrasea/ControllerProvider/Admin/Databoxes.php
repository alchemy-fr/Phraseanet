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
use Alchemy\Phrasea\Controller\Admin\DataboxesController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Databoxes implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Container $app)
    {
        $app['controller.admin.databoxes'] = function (PhraseaApplication $app) {
            return new DataboxesController($app);
        };
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

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
