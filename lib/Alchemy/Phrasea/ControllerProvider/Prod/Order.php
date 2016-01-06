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
use Alchemy\Phrasea\Controller\Prod\OrderController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Order implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.order'] = $app->share(function (PhraseaApplication $app) {
            return (new OrderController($app))
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

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);
        $ensureOrdersAdmin = function () use ($firewall) {
            $firewall->requireOrdersAdmin();
        };

        $controllers->before(function () use ($firewall) {
            $firewall->requireRight('order');
        });

        $controllers->get('/', 'controller.prod.order:displayOrders')
            ->before($ensureOrdersAdmin)
            ->bind('prod_orders');

        $controllers->post('/', 'controller.prod.order:createOrder')
            ->bind('prod_order_new');

        $controllers->get('/{order_id}/', 'controller.prod.order:displayOneOrder')
            ->before($ensureOrdersAdmin)
            ->bind('prod_order')
            ->assert('order_id', '\d+');

        $controllers->post('/{order_id}/send/', 'controller.prod.order:sendOrder')
            ->before($ensureOrdersAdmin)
            ->bind('prod_order_send')
            ->assert('order_id', '\d+');

        $controllers->post('/{order_id}/deny/', 'controller.prod.order:denyOrder')
            ->before($ensureOrdersAdmin)
            ->bind('prod_order_deny')
            ->assert('order_id', '\d+');

        return $controllers;
    }
}
