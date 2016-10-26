<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Api\BasketController;
use Alchemy\Phrasea\Controller\Api\LazaretController;
use Alchemy\Phrasea\Controller\Api\SearchController;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Alchemy\Phrasea\Order\Controller\ApiOrderController;
use Silex\Application;
use Silex\Controller;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class V2 extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    const VERSION = '2.0.0';

    public function register(Application $app)
    {
        $app['controller.api.v2.baskets'] = $app->share(
            function (PhraseaApplication $app) {
                return (new BasketController($app))
                    ->setDataboxLoggerLocator($app['phraseanet.logger'])
                    ->setDispatcher($app['dispatcher'])
                    ->setJsonBodyHelper($app['json.body_helper']);
            }
        );

        $app['controller.api.v2.lazaret'] = $app->share(
            function (PhraseaApplication $app) {
                return (new LazaretController($app));
            }
        );

        $app['controller.api.v2.search'] = $app->share(
            function (PhraseaApplication $app) {
                return new SearchController($app);
            }
        );

        $app['controller.api.v2.orders'] = $app->share(
            function (PhraseaApplication $app) {
                $controller = new ApiOrderController(
                    $app,
                    $app['repo.orders'],
                    $app['repo.order-elements'],
                    $app['provider.order_basket']
                );

                $controller
                    ->setDispatcher($app['dispatcher'])
                    ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                    ->setDelivererLocator(new LazyLocator($app, 'phraseanet.file-serve'))
                    ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                    ->setJsonBodyHelper($app['json.body_helper']);

                return $controller;
            }
        );
    }

    public function boot(Application $app)
    {
        // Intentionally left empty
    }

    public function connect(Application $app)
    {
        if (! $this->isApiEnabled($app)) {
            return $app['controllers_factory'];
        }

        $controllers = $this->createCollection($app);

        $controllers->before(new OAuthListener());

        $controller = $controllers
            ->post('/baskets/{basket}/records/', 'controller.api.v2.baskets:addRecordsAction')
            ->bind('api_v2_basket_records_add');
        $this->addBasketMiddleware($app, $controller);
        $controllers->post('/baskets/{wrong_basket}/records/', 'controller.api.v1:getBadRequestAction');

        $controller = $controllers->delete('/baskets/{basket}/records/', 'controller.api.v2.baskets:removeRecordsAction')
            ->bind('api_v2_basket_records_remove');
        $this->addBasketMiddleware($app, $controller);
        $controllers->delete('/baskets/{wrong_basket}/records/', 'controller.api.v1:getBadRequestAction');

        $controller = $controllers->put('/baskets/{basket}/records/reorder', 'controller.api.v2.baskets:reorderRecordsAction')
            ->bind('api_v2_basket_records_reorder');
        $this->addBasketMiddleware($app, $controller);

        $controllers->match('/search/', 'controller.api.v2.search:searchAction');

        $controllers->delete('/quarantine/', 'controller.api.v2.lazaret:quarantineItemEmptyAction')
            ->bind('api_v2_quarantine_empty');

        $controller = $controllers->delete('/quarantine/item/{lazaret_id}/', 'controller.api.v2.lazaret:quarantineItemDeleteAction')
            ->bind('api_v2_quarantine_item_delete');
        $this->addQuarantineMiddleware($controller);

        $controller = $controllers->post('/quarantine/item/{lazaret_id}/add/', 'controller.api.v2.lazaret:quarantineItemAddAction')
            ->bind('api_v2_quarantine_item_add');
        $this->addQuarantineMiddleware($controller);

        $controllers->post('/orders/', 'controller.api.v2.orders:createAction')
            ->bind('api_v2_orders_create');
        $controllers->get('/orders/', 'controller.api.v2.orders:indexAction')
            ->bind('api_v2_orders_index');
        $controllers->get('/orders/{orderId}', 'controller.api.v2.orders:showAction')
            ->assert('orderId', '\d+')
            ->bind('api_v2_orders_show');

        $controllers->post('/orders/{orderId}/accept', 'controller.api.v2.orders:acceptElementsAction')
            ->assert('orderId', '\d+')
            ->bind('api_v2_orders_accept');

        $controllers->post('/orders/{orderId}/deny', 'controller.api.v2.orders:denyElementsAction')
            ->assert('orderId', '\d+')
            ->bind('api_v2_orders_deny');

        $controllers->get('/orders/{orderId}/archive', 'controller.api.v2.orders:getArchiveAction')
            ->assert('orderId', '\d+')
            ->bind('api_v2_orders_archive');

        return $controllers;
    }

    private function addBasketMiddleware(Application $app, Controller $controller)
    {
        $controller
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access'])
            ->assert('basket', '\d+');

        return $controller;
    }

    private function addQuarantineMiddleware(Controller $controller)
    {
        $controller
            ->assert('lazaret_id', '\d+');

        return $controller;
    }
}
