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
use Alchemy\Phrasea\Controller\Prod\FeedController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Feed implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.feed'] = $app->share(function (PhraseaApplication $app) {
            return (new FeedController($app))
                ->setDispatcher($app['dispatcher'])
                ->setFirewall($app['firewall']);
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->post('/requestavailable/', 'controller.prod.feed:publishRecordsAction');

        $controllers->post('/entry/create/', 'controller.prod.feed:createFeedEntryAction')
            ->bind('prod_feeds_entry_create')
            ->before('controller.prod.feed:ensureUserHasPublishRight');

        $controllers->get('/entry/{id}/edit/', 'controller.prod.feed:editEntryAction')
            ->bind('prod_feeds_entry_edit')
            ->assert('id', '\d+')
            ->before('controller.prod.feed:ensureUserHasPublishRight');

        $controllers->post('/entry/{id}/update/', 'controller.prod.feed:updateEntryAction')
            ->bind('prod_feeds_entry_update')
            ->assert('id', '\d+')
            ->before('controller.prod.feed:ensureUserHasPublishRight');

        $controllers->post('/entry/{id}/delete/', 'controller.prod.feed:deleteEntryAction')
            ->bind('prod_feeds_entry_delete')
            ->assert('id', '\d+')
            ->before('controller.prod.feed:ensureUserHasPublishRight');

        $controllers->get('/', 'controller.prod.feed:indexAction')
            ->bind('prod_feeds');

        $controllers->get('/feed/{id}/', 'controller.prod.feed:showAction')
            ->bind('prod_feeds_feed')
            ->assert('id', '\d+');

        $controllers->get('/subscribe/aggregated/', 'controller.prod.feed:subscribeAggregatedFeedAction')
            ->bind('prod_feeds_subscribe_aggregated');

        $controllers->get('/subscribe/{id}/', 'controller.prod.feed:subscribeFeedAction')
            ->bind('prod_feeds_subscribe_feed')
            ->assert('id', '\d+');

        $controllers->post('/notify/count/', 'controller.prod.feed:notifyCountAction')
            ->bind('prod_feeds_notify_count');

        return $controllers;
    }
}
