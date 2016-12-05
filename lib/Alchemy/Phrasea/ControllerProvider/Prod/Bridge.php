<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\BridgeController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Bridge implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['bridge.controller'] = $app->share(function (PhraseaApplication $app) {
            return new BridgeController($app);
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
            $firewall->requireRight(\ACL::BAS_CHUPUB);
        });

        $controllers
            ->post('/manager/', 'bridge.controller:doPostManager')
            ->bind('prod_bridge_manager');

        $controllers
            ->get('/login/{api_name}/', 'bridge.controller:doGetLogin')
            ->bind('prod_bridge_login');

        $controllers
            ->get('/callback/{api_name}/', 'bridge.controller:doGetCallback')
            ->bind('prod_bridge_callback');

        $controllers
            ->get('/adapter/{account_id}/logout/', 'bridge.controller:doGetAccountLogout')
            ->bind('prod_bridge_account_logout')
            ->assert('account_id', '\d+');

        $controllers
            ->post('/adapter/{account_id}/delete/', 'bridge.controller:doPostAccountDelete')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/adapter/{account_id}/load-records/', 'bridge.controller:doGetloadRecords')
            ->bind('prod_bridge_account_loadrecords')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/adapter/{account_id}/load-elements/{type}/', 'bridge.controller:doGetLoadElements')
            ->bind('bridge_load_elements')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/adapter/{account_id}/load-containers/{type}/', 'bridge.controller:doGetLoadContainers')
            ->bind('prod_bridge_account_loadcontainers')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/action/{account_id}/{action}/{element_type}/', 'bridge.controller:doGetAction')
            ->bind('bridge_account_action')
            ->assert('account_id', '\d+');

        $controllers
            ->post('/action/{account_id}/{action}/{element_type}/', 'bridge.controller:doPostAction')
            ->bind('bridge_account_do_action')
            ->assert('account_id', '\d+');

        $controllers
            ->get('/upload/', 'bridge.controller:doGetUpload')
            ->bind('prod_bridge_upload');

        $controllers
            ->post('/upload/', 'bridge.controller:doPostUpload')
            ->bind('prod_bridge_do_upload');

        return $controllers;
    }
}
