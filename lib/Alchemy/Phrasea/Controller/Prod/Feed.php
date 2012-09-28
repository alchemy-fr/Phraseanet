<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /**
         * I got a selection of docs, which publications are available forthese docs ?
         */
        $controllers->post('/requestavailable/', function(Application $app, Request $request) {
            $feeds = \Feed_Collection::load_all($app, $app['phraseanet.user']);
            $publishing = RecordsRequest::fromRequest($app, $request, true, array(), array('bas_chupub'));

            return $app['twig']->render('prod/actions/publish/publish.html.twig', array('publishing' => $publishing, 'feeds'      => $feeds));
        });

        /**
         * I've selected a publication for my docs, let's publish them
         */
        $controllers->post('/entry/create/', function(Application $app, Request $request) {
            try {
                $feed = new \Feed_Adapter($app, $request->request->get('feed_id'));
                $publisher = \Feed_Publisher_Adapter::getPublisher($app['phraseanet.appbox'], $feed, $app['phraseanet.user']);

                $title = $request->request->get('title');
                $subtitle = $request->request->get('subtitle');
                $author_name = $request->request->get('author_name');
                $author_mail = $request->request->get('author_mail');

                $entry = \Feed_Entry_Adapter::create($app, $feed, $publisher, $title, $subtitle, $author_name, $author_mail);

                $publishing = RecordsRequest::fromRequest($app, $request, true, array(), array('bas_chupub'));

                foreach ($publishing as $record) {
                    $item = \Feed_Entry_Item::create($app['phraseanet.appbox'], $entry, $record);
                }
                $datas = array('error'   => false, 'message' => false);
            } catch (\Exception $e) {
                $datas = array('error'   => true, 'message' => _('An error occured'), 'details' => $e->getMessage());
            }

            return $app->json($datas);
        });

        $controllers->get('/entry/{id}/edit/', function(Application $app, Request $request, $id) {
            $entry = \Feed_Entry_Adapter::load_from_id($app, $id);

            if ($entry->get_publisher()->get_user()->get_id() !== $app['phraseanet.user']->get_id()) {
                throw new \Exception_UnauthorizedAction();
            }

            $feeds = \Feed_Collection::load_all($app, $app['phraseanet.user']);

            $datas = $app['twig']->render('prod/actions/publish/publish_edit.html.twig', array('entry' => $entry, 'feeds' => $feeds));

            return new Response($datas);
        })->assert('id', '\d+');

        $controllers->post('/entry/{id}/update/', function(Application $app, Request $request, $id) {
            $datas = array('error'   => true, 'message' => '', 'datas'   => '');
            try {
                $app['phraseanet.appbox']->get_connection()->beginTransaction();

                $entry = \Feed_Entry_Adapter::load_from_id($app, $id);

                if ($entry->get_publisher()->get_user()->get_id() !== $app['phraseanet.user']->get_id()) {
                    throw new \Exception_UnauthorizedAction();
                }

                $title = $request->request->get('title');
                $subtitle = $request->request->get('subtitle');
                $author_name = $request->request->get('author_name');
                $author_mail = $request->request->get('author_mail');

                $entry->set_author_email($author_mail)
                    ->set_author_name($author_name)
                    ->set_title($title)
                    ->set_subtitle($subtitle);

                $current_feed_id = $entry->get_feed()->get_id();
                $new_feed_id = $request->request->get('feed_id', $current_feed_id);
                if ($current_feed_id != $new_feed_id) {
                    try {
                        $new_feed = \Feed_Adapter::load_with_user($app, $app['phraseanet.user'], $new_feed_id);
                    } catch (\Exception_NotFound $e) {
                        throw new \Exception_Forbidden('You have no access to this feed');
                    }

                    if (!$new_feed->is_publisher($app['phraseanet.user'])) {
                        throw new \Exception_Forbidden('You are not publisher of this feed');
                    }

                    $entry->set_feed($new_feed);
                }

                $items = explode(';', $request->request->get('sorted_lst'));

                foreach ($items as $item_sort) {
                    $item_sort_datas = explode('_', $item_sort);
                    if (count($item_sort_datas) != 2) {
                        continue;
                    }

                    $item = new \Feed_Entry_Item($app['phraseanet.appbox'], $entry, $item_sort_datas[0]);

                    $item->set_ord($item_sort_datas[1]);
                }
                $app['phraseanet.appbox']->get_connection()->commit();

                $entry = $app['twig']->render('prod/feeds/entry.html.twig', array('entry' => $entry));

                $datas = array('error'   => false, 'message' => 'succes', 'datas'   => $entry);
            } catch (\Exception_Feed_EntryNotFound $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                $datas['message'] = _('Feed entry not found');
            } catch (\Exception_NotFound $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                $datas['message'] = _('Feed not found');
            } catch (\Exception_Forbidden $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                $datas['message'] = _('You are not authorized to access this feed');
            } catch (\Exception $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                $datas['message'] = $e->getMessage();
            }

            return $app->json($datas);
        })->assert('id', '\d+');

        $controllers->post('/entry/{id}/delete/', function(Application $app, Request $request, $id) {
            $datas = array('error'   => true, 'message' => '');
            try {
                $app['phraseanet.appbox']->get_connection()->beginTransaction();

                $entry = \Feed_Entry_Adapter::load_from_id($app, $id);

                if ($entry->get_publisher()->get_user()->get_id() !== $app['phraseanet.user']->get_id()
                    && $entry->get_feed()->is_owner($app['phraseanet.user']) === false) {
                    throw new \Exception_UnauthorizedAction(_('Action Forbidden : You are not the publisher'));
                }

                $entry->delete();

                $app['phraseanet.appbox']->get_connection()->commit();
                $datas = array('error'   => false, 'message' => 'succes');
            } catch (\Exception_Feed_EntryNotFound $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                $datas['message'] = _('Feed entry not found');
            } catch (\Exception $e) {
                $app['phraseanet.appbox']->get_connection()->rollBack();
                $datas['message'] = $e->getMessage();
            }

            return $app->json($datas);
        })->assert('id', '\d+');

        $controllers->get('/', function(Application $app, Request $request) {
            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page > 0 ? $page : 1;

            $feeds = \Feed_Collection::load_all($app, $app['phraseanet.user']);

            $datas = $app['twig']->render('prod/feeds/feeds.html.twig'
                , array(
                'feeds' => $feeds
                , 'feed'  => $feeds->get_aggregate()
                , 'page'  => $page
                )
            );

            return new Response($datas);
        });

        $controllers->get('/feed/{id}/', function(Application $app, Request $request, $id) {
            $page = (int) $request->query->get('page');
            $page = $page > 0 ? $page : 1;

            $feed = \Feed_Adapter::load_with_user($app, $app['phraseanet.user'], $id);
            $feeds = \Feed_Collection::load_all($app, $app['phraseanet.user']);

            $datas = $app['twig']->render('prod/feeds/feeds.html.twig', array('feed'  => $feed, 'feeds' => $feeds, 'page'  => $page));

            return new Response($datas);
        })->assert('id', '\d+');

        $controllers->get('/subscribe/aggregated/', function(Application $app, Request $request) {
            $renew = ($request->query->get('renew') === 'true');

            $feeds = \Feed_Collection::load_all($app, $app['phraseanet.user']);

            $output = array(
                'texte' => '<p>' . _('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                . '</p><p>' . _('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
            <div><input type="text" readonly="readonly" class="input_select_copy" value="' . $feeds->get_aggregate()->get_user_link($app['phraseanet.registry'], $app['phraseanet.user'], \Feed_Adapter::FORMAT_RSS, null, $renew)->get_href() . '"/></div>',
                'titre' => _('publications::votre rss personnel')
            );

            return $app->json($output);
        });

        $controllers->get('/subscribe/{id}/', function(Application $app, Request $request, $id) {
            $renew = ($request->query->get('renew') === 'true');
            $feed = \Feed_Adapter::load_with_user($app, $app['phraseanet.user'], $id);

            $output = array(
                'texte' => '<p>' . _('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                . '</p><p>' . _('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
            <div><input type="text" style="width:100%" value="' . $feed->get_user_link($app['phraseanet.registry'], $app['phraseanet.user'], \Feed_Adapter::FORMAT_RSS, null, $renew)->get_href() . '"/></div>',
                'titre' => _('publications::votre rss personnel')
            );

            return $app->json($output);
        })->assert('id', '\d+');

        return $controllers;
    }
}
