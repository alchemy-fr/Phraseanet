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
use Symfony\Component\HttpFoundation\Response;
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
        $that = $this;

        $controllers->get('/feed/{id}/{format}/', function(Application $app, $id, $format) use ($that) {
            $feed = $app['EM']->getRepository('Entities\Feed')->find($id);

            if (!$feed) {
                $app->abort(404, 'Feed not found');
            }

            if (!$feed->isPublic()) {
                $app->abort(403, 'Forbidden');
            }

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app[$that->getFormater($format)]->createResponse($feed, $page);
        })
            ->bind('feed_public')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/{token}/{id}/{format}/', function(Application $app, $token, $id, $format) use ($that) {
            $token = $app["EM"]->find('Entities\FeedToken', $id);
            $feed = $token->getFeed();
            $usrId = $token->getUsrId();

            $user = \User_Adapter::getInstance($usrId, $app);

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app[$that->getFormater($format)]->createResponse($feed, $page, $user);
        })
            ->bind('feed_user')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/aggregated/{token}/{format}/', function(Application $app, $token, $format) use ($that) {
            $token = $app['EM']->getRepository('Entities\AggregateToken')->findOneBy(array("value" => $token));
            $usrId = $token->getUsrId();

            $user = \User_Adapter::getInstance($usrId, $app);

            $feeds = $app['EM']->getRepository('Entities\Feed')->getAllForUser($user);

            $aggregate = new Aggregate($app['EM'], $feeds, $token);

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app[$that->getFormater($format)]->createResponse($aggregate, $page, $user);
        })
            ->bind('feed_user_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/aggregated/{format}/', function(Application $app, $format) use ($that) {
            $feeds = $app['EM']->getRepository('Entities\Feed')->findAllPublic();
            $feed = new Aggregate($app['EM'], $feeds);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app[$that->getFormater($format)]->createResponse($feed, $page);
        })
            ->bind('feed_public_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/cooliris/', function(Application $app) use ($that) {
            $feeds = $app['EM']->getRepository('Entities\Feed')->findAllPublic();
            $feed = new Aggregate($app['EM'], $feeds);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $app[$that->getFormater('cooliris')]->createResponse($feed, $page, null, 'Phraseanet', $app);
        })
            ->bind('feed_public_cooliris');

        return $controllers;
    }

    private function getFormater($type)
    {
        switch ($type) {
            case 'rss':
                return 'feed.rss-formatter';
                break;
            case 'atom':
                return 'feed.atom-formatter';
                break;
            case 'cooliris':
                return 'feed.cooliris-formatter';
                break;
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $format));
        }
    }
}
