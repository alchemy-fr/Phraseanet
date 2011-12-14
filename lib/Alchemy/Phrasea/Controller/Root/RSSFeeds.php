<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */


class RSSFeeds implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $appbox = \appbox::get_instance();

    $controllers = new ControllerCollection();

    $display_feed = function($feed, $format, $page, $user = null)
            {
              $total = $feed->get_count_total_entries();
              $perPage = 5;
              $entries = $feed->get_entries((($page - 1) * $perPage), $perPage);

              $registry = \registry::get_instance();

              if ($format == 'rss')
              {
                $content = new \Feed_XML_RSS();
              }
              if ($format == 'atom')
              {
                $content = new \Feed_XML_Atom();
              }

              if ($user instanceof \User_Adapter)
                $link = $feed->get_user_link($registry, $user, $format, $page);
              else
                $link = $feed->get_homepage_link($registry, $format, $page);

              $content->set_updated_on(new \DateTime());
              $content->set_title($feed->get_title());
              $content->set_subtitle($feed->get_subtitle());
              $content->set_generator('Phraseanet');
              $content->set_link($link);

              if ($user instanceof \User_Adapter)
              {
                if ($page > 1)
                  $content->set_previous_page($feed->get_user_link($registry, $user, $format, ($page - 1)));
                if ($total > ($page * $perPage))
                  $content->set_next_page($feed->get_user_link($registry, $user, $format, ($page + 1)));
              }
              else
              {
                if ($page > 1)
                  $content->set_previous_page($feed->get_homepage_link($registry, $format, ($page - 1)));
                if ($total > ($page * $perPage))
                  $content->set_next_page($feed->get_homepage_link($registry, $format, ($page + 1)));
              }
              foreach ($entries->get_entries() as $entry)
                $content->set_item($entry);

              $render = $content->render();
              $response = new Response($render, 200, array('Content-Type' => $content->get_mimetype()));
              $response->setCharset('UTF-8');

              return $response;
            };



    $controllers->get('/feed/{id}/{format}/', function($id, $format) use ($app, $appbox, $display_feed)
            {
              $feed = new \Feed_Adapter($appbox, $id);

              if (!$feed->is_public())
              {
                return new Response('Forbidden', 403);
              }

              $request = $app['request'];

              $page = (int) $request->get('page');
              $page = $page < 1 ? 1 : $page;

              return $display_feed($feed, $format, $page);
            })->assert('id', '\d+')->assert('format', '(rss|atom)');



    $controllers->get('/userfeed/{token}/{id}/{format}/', function($token, $id, $format) use ($app, $appbox, $display_feed)
            {
              try
              {
                $token = new \Feed_Token($appbox, $token, $id);
                $feed = $token->get_feed();
              }
              catch (\Exception_FeedNotFound $e)
              {
                return new Response('Not Found', 404);
              }
              $request = $app['request'];

              $page = (int) $request->get('page');
              $page = $page < 1 ? 1 : $page;

              return $display_feed($feed, $format, $page, $token->get_user());
            })->assert('id', '\d+')->assert('format', '(rss|atom)');



    $controllers->get('/userfeed/aggregated/{token}/{format}/', function($token, $format) use ($app, $appbox, $display_feed)
            {
              try
              {
                $token = new \Feed_TokenAggregate($appbox, $token);
                $feed = $token->get_feed();
              }
              catch (\Exception_FeedNotFound $e)
              {
                return new Response('', 404);
              }

              $request = $app['request'];

              $page = (int) $request->get('page');
              $page = $page < 1 ? 1 : $page;

              return $display_feed($feed, $format, $page, $token->get_user());
            })->assert('id', '\d+')->assert('format', '(rss|atom)');



    $controllers->get('/aggregated/{format}/', function($format) use ($app, $appbox, $display_feed)
            {
              $feeds = \Feed_Collection::load_public_feeds($appbox);
              $feed = $feeds->get_aggregate();

              $request = $app['request'];
              $page = (int) $request->get('page');
              $page = $page < 1 ? 1 : $page;

              return $display_feed($feed, $format, $page);
            })->assert('format', '(rss|atom)');

    return $controllers;
  }

}