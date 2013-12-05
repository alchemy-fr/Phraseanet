<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Feed\Aggregate;
use Silex\Application;
use Silex\ControllerProviderInterface;

class RSSFeeds implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.rss-feeds'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->get('/feed/{id}/{format}/', function (Application $app, $id, $format) {
            $feed = $app['EM']->getRepository('Phraseanet:Feed')->find($id);

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
        })
            ->bind('feed_public')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/{token}/{id}/{format}/', function (Application $app, $token, $id, $format) {
            $token = $app["EM"]->find('Phraseanet:FeedToken', $id);

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)
                ->createResponse($app, $token->getFeed(), $page, $token->getUser());
        })
            ->bind('feed_user')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/aggregated/{token}/{format}/', function (Application $app, $token, $format) {
            $token = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken')->findOneBy(["value" => $token]);

            $user = $token->getUser();

            $feeds = $app['EM']->getRepository('Phraseanet:Feed')->getAllForUser($app['acl']->get($user));

            $aggregate = new Aggregate($app['EM'], $feeds, $token);

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)->createResponse($app, $aggregate, $page, $user);
        })
            ->bind('feed_user_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/aggregated/{format}/', function (Application $app, $format) {
            $feed = Aggregate::getPublic($app);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)->createResponse($app, $feed, $page);
        })
            ->bind('feed_public_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/cooliris/', function (Application $app) {
            $feed = Aggregate::getPublic($app);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']('cooliris')->createResponse($app, $feed, $page, null, 'Phraseanet', $app);
        })
            ->bind('feed_public_cooliris');

        return $controllers;
    }
}
