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
use Alchemy\Phrasea\Controller\Prod\StoryController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Story implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.story'] = $app->share(function (PhraseaApplication $app) {
            return (new StoryController($app))
                ->setDispatcher($app['dispatcher'])
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

        $controllers->get('/create/', 'controller.prod.story:displayCreateFormAction')
            ->bind('prod_stories_create');

        $controllers->post('/', 'controller.prod.story:postCreateFormAction')
            ->bind('prod_stories_do_create');

        /** @uses \Alchemy\Phrasea\Controller\Prod\StoryController::showAction() */
        $controllers->get('/{sbas_id}/{record_id}/', 'controller.prod.story:showAction')
            ->bind('prod_stories_story')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->post('/{sbas_id}/{record_id}/addElements/', 'controller.prod.story:addElementsAction')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->post('/{sbas_id}/{record_id}/delete/{child_sbas_id}/{child_record_id}/', 'controller.prod.story:removeElementAction')
            ->bind('prod_stories_story_remove_element')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->assert('child_sbas_id', '\d+')
            ->assert('child_record_id', '\d+');

        $controllers->get('/{sbas_id}/{record_id}/reorder/', 'controller.prod.story:displayReorderFormAction')
            ->bind('prod_stories_story_reorder')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->post('/{sbas_id}/{record_id}/reorder/', 'controller.prod.story:reorderAction')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
