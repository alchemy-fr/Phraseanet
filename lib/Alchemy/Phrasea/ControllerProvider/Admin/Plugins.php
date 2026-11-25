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
use Alchemy\Phrasea\Controller\Admin\PluginsController;
use Alchemy\Phrasea\Security\Firewall;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;

class Plugins implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['controller.admin_plugin'] = function (PhraseaApplication $app) {
            return new PluginsController($app);
        };
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

        $controllers
            ->get('/', 'controller.admin_plugin:indexAction')
            ->bind('admin_plugins_list');

        $controllers
            ->get('/{pluginName}', 'controller.admin_plugin:showAction')
            ->bind('admin_plugins_show');

        return $controllers;
    }
}
