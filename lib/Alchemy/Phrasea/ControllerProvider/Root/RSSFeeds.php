<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Root;

use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Feed\Aggregate;
use Silex\Application;
use Silex\ControllerProviderInterface;

class RSSFeeds implements ControllerProviderInterface
{
    use ControllerProviderTrait;

    public function connect(Application $app)
    {
        $app['controller.rss-feeds'] = $this;

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

    public function showPublicFeedAction(Application $app, $id, $format)
    {
        $feed = $app['repo.feeds']->find($id);

        if (null === $feed) {
            $app->abort(404, 'Feed not found');
        }

        if (!$feed->isPublic()) {
            $app->abort(403, 'Forbidden');
        }

        $request = $app['request'];

        $page = (int) $request->query->get('page');
        $page = $page < 1 ? 1 : $page;

        return $app['feed.formatter-strategy']($format)->createResponse($app, $feed, $page);
    }

    public function showUserFeedAction(Application $app, $token, $id, $format)
    {
        $token = $app["repo.feed-tokens"]->find($id);

        $request = $app['request'];

        $page = (int) $request->query->get('page');
        $page = $page < 1 ? 1 : $page;

        return $app['feed.formatter-strategy']($format)
            ->createResponse($app, $token->getFeed(), $page, $token->getUser());
    }

    public function showAggregatedUserFeedAction(Application $app, $token, $format)
    {
        $token = $app['repo.aggregate-tokens']->findOneBy(["value" => $token]);

        $user = $token->getUser();

        $feeds = $app['repo.feeds']->getAllForUser($app['acl']->get($user));

        $aggregate = new Aggregate($app['orm.em'], $feeds, $token);

        $request = $app['request'];

        $page = (int) $request->query->get('page');
        $page = $page < 1 ? 1 : $page;

        return $app['feed.formatter-strategy']($format)->createResponse($app, $aggregate, $page, $user);
    }

    public function showCoolirisPublicFeedAction(Application $app)
    {
        $feed = Aggregate::getPublic($app);

        $request = $app['request'];
        $page = (int) $request->query->get('page');
        $page = $page < 1 ? 1 : $page;

        return $app['feed.formatter-strategy']('cooliris')->createResponse($app, $feed, $page, null, 'Phraseanet', $app);
    }

    public function showAggregatedPublicFeedAction(Application $app, $format) {
        $feed = Aggregate::getPublic($app);

        $request = $app['request'];
        $page = (int) $request->query->get('page');
        $page = $page < 1 ? 1 : $page;

        return $app['feed.formatter-strategy']($format)->createResponse($app, $feed, $page);
    }
}
