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
use Alchemy\Phrasea\Controller\Admin\SubdefsController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Subdefs implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.subdefs'] = $app->share(function (PhraseaApplication $app) {
            return new SubdefsController($app);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function (Request $request) use ($firewall) {
            $firewall->requireAccessToModule('admin')
                ->requireRightOnSbas($request->attributes->get('sbas_id'), \ACL::BAS_MODIFY_STRUCT);
        });

        $controllers->get('/{sbas_id}/', 'controller.admin.subdefs:indexAction')
            ->bind('admin_subdefs_subdef')
            ->assert('sbas_id', '\d+');

        $controllers->post('/{sbas_id}/', 'controller.admin.subdefs:changeSubdefsAction')
            ->bind('admin_subdefs_subdef_update')
            ->assert('sbas_id', '\d+');

        return $controllers;
    }
}
