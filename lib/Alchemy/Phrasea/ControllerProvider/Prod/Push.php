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
use Alchemy\Phrasea\Controller\Prod\PushController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
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
        // tranform 'basket' argument (id) to basket object
        $controllers->before($app['middleware.basket.converter']);


        /* click on the toolbar button "push" --> open ux */
        /** @uses \Alchemy\Phrasea\Controller\Prod\PushController::postFormAction */
        $controllers->post('/sendform/', 'controller.prod.push:postFormAction');

        /* click on the toolbar button "share" --> open ux */
        /** @uses \Alchemy\Phrasea\Controller\Prod\PushController::sharebasketFormAction */
        $controllers->post('/sharebasketform/', 'controller.prod.push:sharebasketFormAction');


        /* click on "send" or "save" button on bottom of push/share/feedback ux */
        /** @uses PushController::sendAction() */
        $controllers->post('/send/', 'controller.prod.push:sendAction')
            ->bind('prod_push_send');

        /** @uses PushController::sharebasketAction() */
        $controllers->post('/sharebasket/', 'controller.prod.push:sharebasketAction')
            ->bind('prod_push_send_sharebasket');

        $controllers->post('/update-expiration/', 'controller.prod.push:updateExpirationAction')
            ->bind('prod_push_do_update_expiration');

        $controllers->get('/user/{usr_id}/', 'controller.prod.push:getUserAction')
            ->assert('usr_id', '\d+');

        /** @uses PushController::getListAction */
        $controllers->get('/list/{list_id}/', 'controller.prod.push:getListAction')
            ->bind('prod_push_lists_list')
            ->assert('list_id', '\d+');

        $controllers->post('/add-user/', 'controller.prod.push:addUserAction')
            ->bind('prod_push_do_add_user');

        $controllers->get('/add-user/', 'controller.prod.push:getAddUserFormAction')
            ->bind('prod_push_add_user');

        $controllers->get('/search-user/', 'controller.prod.push:searchUserAction');

        /** @uses PushController::editListAction() */
        $controllers->match('/edit-list/{list_id}/', 'controller.prod.push:editListAction')
            ->bind('prod_push_list_edit')
            ->assert('list_id', '\d+');

        return $controllers;
    }
}
