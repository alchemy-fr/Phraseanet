<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\SetupController;
use Alchemy\Phrasea\Helper\DatabaseHelper;
use Alchemy\Phrasea\Helper\PathHelper;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Setup implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controller.setup'] = $app->share(function (PhraseaApplication $application) {
            return new SetupController($application);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function (PhraseaApplication $app) {
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

        $controllers->get('/connection_test/mysql/', function (PhraseaApplication $app, Request $request) {
            $dbHelper = new DatabaseHelper($app, $request);

            return $app->json($dbHelper->checkConnection());
        });

        $controllers->get('/test/path/', function (PhraseaApplication $app, Request $request) {
            $pathHelper = new PathHelper($app, $request);

            return $app->json($pathHelper->checkPath());
        });

        $controllers->get('/test/url/', function (PhraseaApplication $app, Request $request) {
            $pathHelper = new PathHelper($app, $request);

            return $app->json($pathHelper->checkUrl());
        });

        return $controllers;
    }
}
