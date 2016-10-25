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
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\PushController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Push implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.push'] = $app->share(function (PhraseaApplication $app) {
            return (new PushController($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDispatcher($app['dispatcher'])
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ->setUserQueryFactory(new LazyLocator($app, 'phraseanet.user-query'))
            ;
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
            $firewall->requireRight(\ACL::CANPUSH);
        });

        $controllers->post('/sendform/', 'controller.prod.push:postFormAction');

        $controllers->post('/validateform/', 'controller.prod.push:validateFormAction');

        $controllers->post('/send/', 'controller.prod.push:sendAction')
            ->bind('prod_push_send');

        $controllers->post('/validate/', 'controller.prod.push:validateAction')
            ->bind('prod_push_validate');

        $controllers->get('/user/{usr_id}/', 'controller.prod.push:getUserAction')
            ->assert('usr_id', '\d+');

        $controllers->get('/list/{list_id}/', 'controller.prod.push:getListAction')
            ->bind('prod_push_lists_list')
            ->assert('list_id', '\d+');

        $controllers->post('/add-user/', 'controller.prod.push:addUserAction')
            ->bind('prod_push_do_add_user');

        $controllers->get('/add-user/', 'controller.prod.push:getAddUserFormAction')
            ->bind('prod_push_add_user');

        $controllers->get('/search-user/', 'controller.prod.push:searchUserAction');

        $controllers->match('/edit-list/{list_id}/', 'controller.prod.push:editListAction')
            ->bind('prod_push_list_edit')
            ->assert('list_id', '\d+');

        return $controllers;
    }
}
