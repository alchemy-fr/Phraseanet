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

        $display_feed = function(Application $app, $feed, $format, $page, $user = null) {
            $total = $feed->getCountTotalEntries();
            $perPage = 5;
            $entries = $feed->getEntries((($page - 1) * $perPage), $perPage);

            if ($format == \Entities\Feed::FORMAT_RSS) {
                $content = new \Feed_XML_RSS();
            }

            if ($format == \Entities\Feed::FORMAT_ATOM) {
                $content = new \Feed_XML_Atom();
            }

            if ($format == \Entities\Feed::FORMAT_COOLIRIS) {
                $content = new \Feed_XML_Cooliris();
            }

            if ($user instanceof \User_Adapter)
                $link = $feed->getUserLink($app['phraseanet.registry'], $user, $format, $page);
            else
                $link = $feed->get_homepage_link($app['phraseanet.registry'], $format, $page);

            $content->set_updated_on(new \DateTime());
            $content->set_title($feed->getTitle());
            $content->set_subtitle($feed->getSubtitle());
            $content->set_generator('Phraseanet');
            $content->set_link($link);

            if ($user instanceof \User_Adapter) {
                if ($page > 1)
                    $content->set_previous_page($feed->getUserLink($app['phraseanet.registry'], $user, $format, ($page - 1)));
                if ($total > ($page * $perPage))
                    $content->set_next_page($feed->getUserLink($app['phraseanet.registry'], $user, $format, ($page + 1)));
            } else {
                if ($page > 1)
                    $content->set_previous_page($feed->get_homepage_link($app['phraseanet.registry'], $format, ($page - 1)));
                if ($total > ($page * $perPage))
                    $content->set_next_page($feed->get_homepage_link($app['phraseanet.registry'], $format, ($page + 1)));
            }
            foreach ($entries->getEntries() as $entry)
                $content->set_item($entry);

            $render = $content->render();
            $response = new Response($render, 200, array('Content-Type' => $content->get_mimetype()));
            $response->setCharset('UTF-8');

            return $response;
        };

        $controllers->get('/feed/{id}/{format}/', function(Application $app, $id, $format) use ($display_feed) {
           // $feed = new \Feed_Adapter($app, $id);
            $feed = $app["EM"]->getRepository("Entities\Feed")->find($id);

            if (!$feed->isPublic()) {
                return new Response('Forbidden', 403);
            }

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $display_feed($app, $feed, $format, $page);
        })
            ->bind('feed_public')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/{token}/{id}/{format}/', function(Application $app, $token, $id, $format) use ($display_feed) {
            $token = $app["EM"]->getRepository("Entities\FeedToken")->findBy(array("id" => $id));
            $feed = $token->getFeed();

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $display_feed($app, $feed, $format, $page, $token->get_user());
        })
            ->bind('feed_user')
            ->assert('id', '\d+')
            ->assert('format', '(rss|atom)');

        $controllers->get('/userfeed/aggregated/{token}/{format}/', function(Application $app, $token, $format) use ($display_feed) {
            $token = $app["EM"]->getRepository("Entities\AggregateToken")->findBy(array("id" => $id));
            //$feed = $token->getFeed();

            $request = $app['request'];

            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $display_feed($app, $feed, $format, $page, $token->get_user());
        })
            ->bind('feed_user_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/aggregated/{format}/', function(Application $app, $format) use ($display_feed) {
            //$feeds = \Feed_Collection::load_public_feeds($app);
            //$feed = $feeds->get_aggregate();
            $feeds = $app["EM"]->getRepository("Entities\Feed")->findAllPublic();
            $feed = new Aggregate($app, $feeds);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $display_feed($app, $feed, $format, $page);
        })
            ->bind('feed_public_aggregated')
            ->assert('format', '(rss|atom)');

        $controllers->get('/cooliris/', function(Application $app) use ($display_feed) {
//            $feeds = \Feed_Collection::load_public_feeds($app);
//            $feed = $feeds->get_aggregate();
            $feeds = $app["EM"]->getRepository("Entities\Feed")->findAllPublic();
            $feed = new Aggregate($app, $feeds);

            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page < 1 ? 1 : $page;

            return $display_feed($app, $feed, \Feed_Adapter::FORMAT_COOLIRIS, $page);
        })
            ->bind('feed_public_cooliris');

        return $controllers;
    }
}
