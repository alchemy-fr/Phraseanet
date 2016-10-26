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
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Order\Controller\ProdOrderController;
use Alchemy\Phrasea\Order\OrderBasketProvider;
use Alchemy\Phrasea\Order\OrderValidator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Order implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['provider.order_basket'] = $app->share(function (PhraseaApplication $app) {
            return new OrderBasketProvider($app['orm.em'], $app['translator']);
        });

        $app['validator.order'] = $app->share(function (PhraseaApplication $app) {
            $orderValidator = new OrderValidator($app['phraseanet.appbox'], $app['repo.collection-references']);
            $orderValidator->setAclProvider($app['acl']);

            return $orderValidator;
        });

        $app['controller.prod.order'] = $app->share(function (PhraseaApplication $app) {
            $controller = new ProdOrderController(
                $app,
                $app['repo.orders'],
                $app['repo.order-elements'],
                $app['provider.order_basket']
            );

            $controller
                ->setDispatcher($app['dispatcher'])
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ->setUserQueryFactory(new LazyLocator($app, 'phraseanet.user-query'));

            return $controller;
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
