<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Order implements ControllerProviderInterface
{
    use ControllerProviderTrait;

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.order'] = $this;

        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('order');
        });

        $controllers->get('/', 'controller.prod.order:displayOrders')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_orders');

        $controllers->post('/', 'controller.prod.order:createOrder')
            ->bind('prod_order_new');

        $controllers->get('/{order_id}/', 'controller.prod.order:displayOneOrder')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order')
            ->assert('order_id', '\d+');

        $controllers->post('/{order_id}/send/', 'controller.prod.order:sendOrder')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order_send')
            ->assert('order_id', '\d+');

        $controllers->post('/{order_id}/deny/', 'controller.prod.order:denyOrder')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order_deny')
            ->assert('order_id', '\d+');

        return $controllers;
    }
}
