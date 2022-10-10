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
use Alchemy\Phrasea\Controller\Admin\ConnectedUsersController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class ConnectedUsers implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.connected-users'] = $app->share(function (PhraseaApplication $app) {
            return new ConnectedUsersController($app);
        });

        $app['twig'] = $app->share($app->extend('twig', function (\Twig_Environment $twig, Application $app) {
            $twig->addFilter(new \Twig_SimpleFilter('AppName', function ($value) use ($app) {
                /** @var ConnectedUsersController $controller */
                $controller = $app['controller.admin.connected-users'];
                return $controller->getModuleNameFromId($value);
            }));

            return $twig;
        }));
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAccessToModule('Admin');
        });

        $controllers->get('/', 'controller.admin.connected-users:listConnectedUsers')
            ->bind('admin_connected_users');

        $controllers->post('/{session_id}/disconnect', 'controller.admin.connected-users:disconnectSessionId')
            ->bind('admin_session_id_disconnect')
            ->assert('session_id', '\d+')
        ;

        return $controllers;
    }
}
