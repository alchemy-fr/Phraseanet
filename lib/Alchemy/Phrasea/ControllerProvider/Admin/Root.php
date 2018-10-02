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
use Alchemy\Phrasea\Controller\Admin\RootController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Root implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.root'] = $app->share(function (PhraseaApplication $app) {
            return new RootController($app);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAccessToModule('admin');
        });

        $controllers->get('/', 'controller.admin.root:indexAction')
            ->bind('admin');

        $controllers->get('/tree/', 'controller.admin.root:displayTreeAction')
            ->bind('admin_display_tree');

        $controllers->get('/test-paths/', 'controller.admin.root:testPathsAction')
            ->bind('admin_test_paths');

        $controllers->get('/structure/{databox_id}/', 'controller.admin.root:displayDataboxStructureAction')
            ->assert('databox_id', '\d+')
            ->bind('database_display_stucture');

        $controllers->post('/structure/{databox_id}/', 'controller.admin.root:submitDatabaseStructureAction')
            ->assert('databox_id', '\d+')
            ->bind('database_submit_stucture');

        $controllers->get('/statusbit/{databox_id}/', 'controller.admin.root:displayStatusBitAction')
            ->assert('databox_id', '\d+')
            ->bind('database_display_statusbit');

        $controllers
            ->get('/statusbit/{databox_id}/status/{bit}/', 'controller.admin.root:displayDatabaseStatusBitFormAction')
            ->assert('databox_id', '\d+')
            ->assert('bit', '\d+')
            ->bind('database_display_statusbit_form');

        $controllers
            ->post('/statusbit/{databox_id}/status/{bit}/delete/', 'controller.admin.root:deleteStatusBitAction')
            ->bind('admin_statusbit_delete')
            ->assert('databox_id', '\d+')
            ->assert('bit', '\d+');

        $controllers->post('/statusbit/{databox_id}/status/{bit}/', 'controller.admin.root:submitStatusBitAction')
            ->assert('databox_id', '\d+')
            ->assert('bit', '\d+')
            ->bind('database_submit_statusbit');

        return $controllers;
    }
}
