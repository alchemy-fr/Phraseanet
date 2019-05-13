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
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\WorkzoneController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class WorkZone implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.workzone'] = $app->share(function (PhraseaApplication $app) {
            return (new WorkzoneController($app))
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

        $controllers
            // Silex\Route::convert is not used as this should be done prior the before middleware
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access']);;

        $controllers->get('/', 'controller.prod.workzone:displayWorkzone')
            ->bind('prod_workzone_show');

        $controllers->get('/Browse/', 'controller.prod.workzone:browse')
            ->bind('prod_workzone_browse');

        $controllers->get('/Browse/Search/', 'controller.prod.workzone:browserSearch')
            ->bind('prod_workzone_search');

        $controllers->get('/Browse/Basket/{basket}/', 'controller.prod.workzone:browseBasket')
            ->bind('prod_workzone_basket')
            ->assert('basket', '\d+');

        $controllers->post('/attachStories/', 'controller.prod.workzone:attachStories');

        $controllers->post('/detachStory/{sbas_id}/{record_id}/', 'controller.prod.workzone:detachStory')
            ->bind('prod_workzone_detach_story')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
