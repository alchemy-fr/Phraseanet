<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Entities\Feed;
use Alchemy\Phrasea\Feed\Aggregate;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class RSSFeeds implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/feed/{id}/{format}/', function(Application $app, $id, $format) {
            $feed = $app['EM']->getRepository('Entities\Feed')->find($id);

            if (null === $feed) {
                $app->abort(404, 'Feed not found');
            }

            if (!$feed->isPublic()) {
                $app->abort(403, 'Forbidden');
            }

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)->createResponse($feed, $page);
        })
            ->bind('feed_public')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/{token}/{id}/{format}/', function(Application $app, $token, $id, $format) {
            $token = $app["EM"]->find('Entities\FeedToken', $id);

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)
                ->createResponse($token->getFeed(), $page, \User_Adapter::getInstance($token->getUsrId(), $app));
        })
            ->bind('feed_user')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/aggregated/{token}/{format}/', function(Application $app, $token, $format) {
            $token = $app['EM']->getRepository('Entities\AggregateToken')->findOneBy(array("value" => $token));

            $user = \User_Adapter::getInstance($token->getUsrId(), $app);

            $feeds = $app['EM']->getRepository('Entities\Feed')->getAllForUser($user);

            $aggregate = new Aggregate($app['EM'], $feeds, $token);

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)->createResponse($aggregate, $page, $user);
        })
            ->bind('feed_user_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/aggregated/{format}/', function(Application $app, $format) {
            $feed = Aggregate::getPublic($app);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']($format)->createResponse($feed, $page);
        })
            ->bind('feed_public_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/cooliris/', function(Application $app) {
            $feed = Aggregate::getPublic($app);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app['feed.formatter-strategy']('cooliris')->createResponse($feed, $page, null, 'Phraseanet', $app);
        })
            ->bind('feed_public_cooliris');

        return $controllers;
    }
}
