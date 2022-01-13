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
use Alchemy\Phrasea\Controller\Prod\UsersListsController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class UsersLists implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.userslists'] = $app->share(function (PhraseaApplication $app) {
            return (new UsersListsController($app))
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

        /* click on the toolbar (push...) to push/share/feedback --> open ux */
        $controllers->post('/sendform/', 'controller.prod.userslists:postFormAction');

        $controllers->get('/list/{list_id}/', 'controller.prod.userslists:getListAction')
            ->bind('prod_push_lists_list')
            ->assert('list_id', '\d+');

        $controllers->post('/add-user/', 'controller.prod.userslists:addUserAction')
            ->bind('prod_push_do_add_user');

        $controllers->get('/add-user/', 'controller.prod.userslists:getAddUserFormAction')
            ->bind('prod_push_add_user');

        $controllers->get('/search-user/', 'controller.prod.userslists:searchUserAction');

        $controllers->match('/edit-list/{list_id}/', 'controller.prod.userslists:editListAction')
            ->bind('prod_push_list_edit')
            ->assert('list_id', '\d+');

        return $controllers;
    }
}
