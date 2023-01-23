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
use Alchemy\Phrasea\Controller\Prod\BasketController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class BasketProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.basket'] = $app->share(function (PhraseaApplication $app) {
            return (new BasketController($app))
                ->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers
            // Silex\Route::convert is not used as this should be done prior the before middleware
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access']);

        /** @uses   \Alchemy\Phrasea\Controller\Prod\BasketController::displayBasket() */
        $controllers->get('/{basket}/', 'controller.prod.basket:displayBasket')
            ->bind('prod_baskets_basket')
            ->assert('basket', '\d+');

        /** @uses   \Alchemy\Phrasea\Controller\Prod\BasketController::getWip() */
        $controllers->get('/{basket}/wip/', 'controller.prod.basket:getWip')
            ->bind('prod_baskets_getwip')
            ->assert('basket', '\d+');

        $controllers->get('/{basket}/reminder/', 'controller.prod.basket:displayReminder')
            ->bind('prod_baskets_reminder')
            ->assert('basket', '\d+');

        $controllers->post('/{basket}/reminder/', 'controller.prod.basket:doReminder')
            ->bind('prod_baskets_do_reminder')
            ->assert('basket', '\d+');

        $controllers->post('/', 'controller.prod.basket:createBasket')
            ->bind('prod_baskets');

        $controllers->post('/{basket}/delete/', 'controller.prod.basket:deleteBasket')
            ->assert('basket', '\d+')
            ->bind('basket_delete')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/delete/{basket_element_id}/', 'controller.prod.basket:removeBasketElement')
            ->bind('prod_baskets_basket_element_remove')
            ->assert('basket', '\d+')
            ->assert('basket_element_id', '\d+')
            ->before($app['middleware.basket.user-can-modify-content']);

        $controllers->post('/{basket}/update/', 'controller.prod.basket:updateBasket')
            ->bind('prod_baskets_basket_update')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->get('/{basket}/update/', 'controller.prod.basket:displayUpdateForm')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->get('/{basket}/reorder/', 'controller.prod.basket:displayReorderForm')
            ->assert('basket', '\d+')
            ->bind('prod_baskets_basket_reorder')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/reorder/', 'controller.prod.basket:reorder')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/archive/', 'controller.prod.basket:archiveBasket')
            ->bind('prod_baskets_basket_archive')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->post('/{basket}/addElements/', 'controller.prod.basket:addElements')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-can-modify-content']);

        $controllers->post('/{basket}/stealElements/', 'controller.prod.basket:stealElements')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.user-is-owner']);

        $controllers->get('/create/', 'controller.prod.basket:displayCreateForm')
            ->bind('prod_baskets_create');

        return $controllers;
    }
}
