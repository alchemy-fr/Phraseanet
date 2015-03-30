<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\SetupController;
use Alchemy\Phrasea\Helper\DatabaseHelper;
use Alchemy\Phrasea\Helper\PathHelper;
use Silex\ControllerProviderInterface;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Setup implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['controller.setup'] = $app->share(function ($application) {
            return new SetupController($application);
        });
    }

    public function boot(SilexApplication $app)
    {
    }

    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function (Application $app) {
            return $app->redirectPath('install_root');
        })->bind('setup');

        $controllers->get('/installer/', 'controller.setup:rootInstaller')
            ->bind('install_root');

        $controllers->get('/upgrade-instructions/', 'controller.setup:displayUpgradeInstructions')
            ->bind('setup_upgrade_instructions');

        $controllers->get('/installer/step2/', 'controller.setup:getInstallForm')
            ->bind('install_step2');

        $controllers->post('/installer/install/', 'controller.setup:doInstall')
            ->bind('install_do_install');

        $controllers->get('/connection_test/mysql/', function (Application $app, Request $request) {
            $dbHelper = new DatabaseHelper($app, $request);

            return $app->json($dbHelper->checkConnection());
        });

        $controllers->get('/test/path/', function (Application $app, Request $request) {
            $pathHelper = new PathHelper($app, $request);

            return $app->json($pathHelper->checkPath());
        });

        $controllers->get('/test/url/', function (Application $app, Request $request) {
            $pathHelper = new PathHelper($app, $request);

            return $app->json($pathHelper->checkUrl());
        });

        return $controllers;
    }
}
