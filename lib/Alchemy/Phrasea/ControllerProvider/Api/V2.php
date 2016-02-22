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
use Alchemy\Phrasea\Controller\Api\SearchController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\Controller;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class V2 implements ControllerProviderInterface, ServiceProviderInterface
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

        $app['controller.api.v2.search'] = $app->share(
            function (PhraseaApplication $app) {
                return new SearchController($app);
            }
        );
    }

    public function boot(Application $app)
    {
        // Intentionally left empty
    }

    public function connect(Application $app)
    {
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
}
