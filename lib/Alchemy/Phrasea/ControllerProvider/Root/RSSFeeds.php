<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Root;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Root\RSSFeedController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class RSSFeeds implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.rss-feeds'] = $app->share(function (PhraseaApplication $app) {
            return (new RSSFeedController($app))
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
        $controllers = $this->createCollection($app);
        $controllers->get('/feed/{id}/{format}/', 'controller.rss-feeds:showPublicFeedAction')
            ->bind('feed_public')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/{token}/{id}/{format}/', 'controller.rss-feeds:showUserFeedAction')
            ->bind('feed_user')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/aggregated/{token}/{format}/', 'controller.rss-feeds:showAggregatedUserFeedAction')
            ->bind('feed_user_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/aggregated/{format}/', 'controller.rss-feeds:showAggregatedPublicFeedAction')
            ->bind('feed_public_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/cooliris/', 'controller.rss-feeds:showCoolirisPublicFeedAction')
            ->bind('feed_public_cooliris');

        return $controllers;
    }
}
