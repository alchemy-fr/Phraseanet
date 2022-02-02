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
use Alchemy\Phrasea\Controller\Prod\UsrListController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class UsrLists implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.usr-lists'] = $app->share(function (PhraseaApplication $app) {
            return (new UsrListController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
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

        /** @uses UsrListController::getAll() */
        $controllers->get('/all/', 'controller.prod.usr-lists:getAll')
            ->bind('prod_lists_all');

        $controllers->post('/list/', 'controller.prod.usr-lists:createList')
            ->bind('prod_lists_list');

        $controllers->get('/list/{list_id}/', 'controller.prod.usr-lists:displayList')
            ->assert('list_id', '\d+');

        $controllers->post('/list/{list_id}/update/', 'controller.prod.usr-lists:updateList')
            ->bind('prod_lists_list_update')
            ->assert('list_id', '\d+');

        $controllers->post('/list/{list_id}/delete/', 'controller.prod.usr-lists:removeList')
            ->assert('list_id', '\d+');

        $controllers->post('/list/{list_id}/remove/{usr_id}/', 'controller.prod.usr-lists:removeUser')
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        $controllers->post('/list/{list_id}/add/', 'controller.prod.usr-lists:addUsers')
            ->assert('list_id', '\d+');

        $controllers->get('/list/{list_id}/share/', 'controller.prod.usr-lists:displayShares')
            ->assert('list_id', '\d+')
            ->bind('prod_lists_list_share');

        $controllers->post('/list/{list_id}/share/{usr_id}/', 'controller.prod.usr-lists:shareWithUser')
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        $controllers->post('/list/{list_id}/unshare/{usr_id}/', 'controller.prod.usr-lists:unshareWithUser')
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        return $controllers;
    }
}
