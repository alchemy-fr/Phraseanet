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
use Alchemy\Phrasea\Controller\Admin\SetupController;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Setup implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controller.admin.setup'] = $app->share(function (PhraseaApplication $app) {
            return new SetupController($app, $app['registry.manipulator'], $app['conf']);
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
        $controllers->before(function () use ($firewall) {
            $firewall->requireAdmin();
        });

        $controllers->match('/', 'controller.admin.setup:submitGlobalsAction')
            ->bind('setup_display_globals')
            ->method('GET|POST');

        return $controllers;
    }
}
