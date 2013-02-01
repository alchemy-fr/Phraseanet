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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

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
        /* @var $twig \Twig_Environment */
        $twig = $app['Core']->getTwig();
        $appbox = \appbox::get_instance($app['Core']);

        /**
         * I got a selection of docs, which publications are available forthese docs ?
         */
        $controllers->post('/requestavailable/', function(Application $app, Request $request) use ($appbox, $twig) {
                $user = $app["Core"]->getAuthenticatedUser();
                $feeds = \Feed_Collection::load_all($appbox, $user);
                $publishing = new RecordHelper\Feed($app['Core'], $request);

                $datas = $twig->render('prod/actions/publish/publish.html', array('publishing' => $publishing, 'feeds'      => $feeds));

                return new Response($datas);
            });

        /**
         * I've selected a publication for my ocs, let's publish them
         */
        $controllers->post('/entry/create/', function(Application $app, Request $request) use ($appbox, $twig) {
                try {
                    $user = $app["Core"]->getAuthenticatedUser();
                    $feed = new \Feed_Adapter($appbox, $request->get('feed_id'));
                    $publisher = \Feed_Publisher_Adapter::getPublisher($appbox, $feed, $user);

                    $title = $request->get('title');
                    $subtitle = $request->get('subtitle');
                    $author_name = $request->get('author_name');
                    $author_mail = $request->get('author_mail');

                    $entry = \Feed_Entry_Adapter::create($appbox, $feed, $publisher, $title, $subtitle, $author_name, $author_mail);

                    $publishing = new RecordHelper\Feed($app['Core'], $app['request']);

                    foreach ($publishing->get_elements() as $record) {
                        $item = \Feed_Entry_Item::create($appbox, $entry, $record);
                    }
                    $datas = array('error'   => false, 'message' => false);
                } catch (\Exception $e) {
                    $datas = array('error'   => true, 'message' => _('An error occured'), 'details' => $e->getMessage());
                }

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($datas, 'json')
                        , 200
                        , array('Content-Type' => 'application/json')
                );
            });

        $controllers->get('/entry/{id}/edit/', function(Application $app, Request $request, $id) use ($appbox, $twig) {

                $user = $app["Core"]->getAuthenticatedUser();

                $entry = \Feed_Entry_Adapter::load_from_id($appbox, $id);

                if (!$entry->is_publisher($user)) {
                    throw new \Exception_UnauthorizedAction();
                }

                $feeds = \Feed_Collection::load_all($appbox, $user);

                $datas = $twig->render('prod/actions/publish/publish_edit.html', array('entry' => $entry, 'feeds' => $feeds));

                return new Response($datas);
            })->assert('id', '\d+');

        $controllers->post('/entry/{id}/update/', function(Application $app, Request $request, $id) use ($appbox, $twig) {
                $datas = array('error'   => true, 'message' => '', 'datas'   => '');
                try {
                    $appbox->get_connection()->beginTransaction();

                    $user = $app["Core"]->getAuthenticatedUser();

                    $entry = \Feed_Entry_Adapter::load_from_id($appbox, $id);

                    if (!$entry->is_publisher($user)) {
                        throw new \Exception_UnauthorizedAction();
                    }

                    $title = $request->get('title');
                    $subtitle = $request->get('subtitle');
                    $author_name = $request->get('author_name');
                    $author_mail = $request->get('author_mail');

                    $entry->set_author_email($author_mail)
                        ->set_author_name($author_name)
                        ->set_title($title)
                        ->set_subtitle($subtitle);

                    $current_feed_id = $entry->get_feed()->get_id();
                    $new_feed_id = $request->get('feed_id',$current_feed_id);
                    if ($current_feed_id != $new_feed_id) {
                        try {
                            $new_feed = \Feed_Adapter::load_with_user($appbox, $user, $new_feed_id);
                        } catch (\Exception_NotFound $e) {
                            throw new \Exception_Forbidden('You have no access to this feed');
                        }

                        if ( ! $new_feed->is_publisher($user)) {
                            throw new \Exception_Forbidden('You are not publisher of this feed');
                        }

                        $entry->set_feed($new_feed);
                    }

                    $items = explode(';', $request->get('sorted_lst'));

                    foreach ($items as $item_sort) {
                        $item_sort_datas = explode('_', $item_sort);
                        if (count($item_sort_datas) != 2) {
                            continue;
                        }

                        $item = new \Feed_Entry_Item($appbox, $entry, $item_sort_datas[0]);

                        $item->set_ord($item_sort_datas[1]);
                    }
                    $appbox->get_connection()->commit();

                    $entry = $twig->render('prod/feeds/entry.html', array('entry' => $entry));

                    $datas = array('error'   => false, 'message' => 'succes', 'datas'   => $entry);
                } catch (\Exception_Feed_EntryNotFound $e) {
                    $appbox->get_connection()->rollBack();
                    $datas['message'] = _('Feed entry not found');
                } catch (\Exception_NotFound $e) {
                    $appbox->get_connection()->rollBack();
                    $datas['message'] = _('Feed not found');
                } catch (\Exception_Forbidden $e) {
                    $appbox->get_connection()->rollBack();
                    $datas['message'] = _('You are not authorized to access this feed');
                } catch (\Exception $e) {
                    $appbox->get_connection()->rollBack();
                    $datas['message'] = $e->getMessage();
                }

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($datas, 'json')
                        , 200
                        , array('Content-Type' => 'application/json')
                );
            })->assert('id', '\d+');

        $controllers->post('/entry/{id}/delete/', function(Application $app, Request $request, $id) use ($appbox, $twig) {
                $datas = array('error'   => true, 'message' => '');
                try {
                    $appbox->get_connection()->beginTransaction();

                    $user = $app["Core"]->getAuthenticatedUser();

                    $entry = \Feed_Entry_Adapter::load_from_id($appbox, $id);

                    if (!$entry->is_publisher($user)
                        && $entry->get_feed()->is_owner($user) === false) {
                        throw new \Exception_UnauthorizedAction(_('Action Forbidden : You are not the publisher'));
                    }

                    $entry->delete();

                    $appbox->get_connection()->commit();
                    $datas = array('error'   => false, 'message' => 'succes');
                } catch (\Exception_Feed_EntryNotFound $e) {
                    $appbox->get_connection()->rollBack();
                    $datas['message'] = _('Feed entry not found');
                } catch (\Exception $e) {
                    $appbox->get_connection()->rollBack();
                    $datas['message'] = $e->getMessage();
                }

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($datas, 'json')
                        , 200
                        , array('Content-Type' => 'application/json')
                );
            })->assert('id', '\d+');

//$app->post('/entry/{id}/addelement/', function($id) use ($app, $appbox, $twig)
//        {
//
//        });
//
//$app->post('/element/{id}/update/', function($id) use ($app, $appbox, $twig)
//        {
//
//        });
//
//$app->post('/element/{id}/delete/', function($id) use ($app, $appbox, $twig)
//        {
//
//        });
//$app->get('/entry/{id}/', function($id) use ($app, $appbox, $twig)
//        {
//
//        });

        $controllers->get('/', function(Application $app, Request $request) use ($appbox, $twig) {
                $request = $app['request'];
                $page = (int) $request->get('page');
                $page = $page > 0 ? $page : 1;

                $user = $app["Core"]->getAuthenticatedUser();

                $feeds = \Feed_Collection::load_all($appbox, $user);

                $datas = $twig->render('prod/feeds/feeds.html'
                    , array(
                    'feeds' => $feeds
                    , 'feed'  => $feeds->get_aggregate()
                    , 'page'  => $page
                    )
                );

                return new Response($datas);
            });

        $controllers->get('/feed/{id}/', function(Application $app, Request $request, $id) use ($appbox, $twig) {
                $page = (int) $request->get('page');
                $page = $page > 0 ? $page : 1;

                $user = $app["Core"]->getAuthenticatedUser();

                $feed = \Feed_Adapter::load_with_user($appbox, $user, $id);
                $feeds = \Feed_Collection::load_all($appbox, $user);

                $datas = $twig->render('prod/feeds/feeds.html', array('feed'  => $feed, 'feeds' => $feeds, 'page'  => $page));

                return new Response($datas);
            })->assert('id', '\d+');

        $controllers->get('/subscribe/aggregated/', function(Application $app, Request $request) use ( $appbox, $twig) {
                $renew = ($request->get('renew') === 'true');

                $user = $app["Core"]->getAuthenticatedUser();

                $feeds = \Feed_Collection::load_all($appbox, $user);
                $registry = $appbox->get_registry();

                $output = array(
                    'texte' => '<p>' . _('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                    . '</p><p>' . _('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
                <div><input type="text" readonly="readonly" class="input_select_copy" value="' . $feeds->get_aggregate()->get_user_link($registry, $user, \Feed_Adapter::FORMAT_RSS, null, $renew)->get_href() . '"/></div>',
                    'titre' => _('publications::votre rss personnel')
                );

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($output, 'json')
                        , 200
                        , array('Content-Type' => 'application/json')
                );
            });

        $controllers->get('/subscribe/{id}/', function(Application $app, Request $request, $id) use ($appbox, $twig) {
                $renew = ($request->get('renew') === 'true');
                $user = $app["Core"]->getAuthenticatedUser();
                $feed = \Feed_Adapter::load_with_user($appbox, $user, $id);
                $registry = $appbox->get_registry();

                $output = array(
                    'texte' => '<p>' . _('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                    . '</p><p>' . _('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
                <div><input type="text" style="width:100%" value="' . $feed->get_user_link($registry, $user, \Feed_Adapter::FORMAT_RSS, null, $renew)->get_href() . '"/></div>',
                    'titre' => _('publications::votre rss personnel')
                );

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($output, 'json')
                        , 200
                        , array('Content-Type' => 'application/json')
                );
            })->assert('id', '\d+');

        return $controllers;
    }
}
