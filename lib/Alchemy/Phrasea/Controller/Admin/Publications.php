<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

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
class Publications implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $appbox = \appbox::get_instance();
    $session = $appbox->get_session();

    $controllers = new ControllerCollection();

    $controllers->get('/list/', function() use ($app, $appbox)
            {
              $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
              $feeds = \Feed_Collection::load_all($appbox, $user);

              $template = 'admin/publications/list.html';

              $twig = new \supertwig();
              $twig->addFilter(array('formatdate' => 'phraseadate::getDate'));

              return $twig->render($template, array('feeds' => $feeds));
            });


    $controllers->post('/create/', function() use ($app, $appbox)
            {

              $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
              $request = $app['request'];

              $feed = \Feed_Adapter::create($appbox, $user, $request->get('title'), $request->get('subtitle'));
              
              if($request->get('public') == '1')
                $feed->set_public (true);
              elseif ($request->get('base_id'))
                $feed->set_collection(\collection::get_from_base_id($request->get('base_id')));

              return $app->redirect('/admin/publications/list/');
            });


    $controllers->get('/feed/{id}/', function($id) use ($app, $appbox)
            {
              $feed = new \Feed_Adapter($appbox, $id);

              $template = 'admin/publications/fiche.html';

              $twig = new \supertwig();
              $twig->addFilter(
                      array(
                          'formatdate' => 'phraseadate::getDate'
                      )
              );

              return $twig->render($template
                              , array(
                          'feed' => $feed
                          , 'error' => $app['request']->get('error')
                              )
              );
            });


    $controllers->post('/feed/{id}/update/', function($id) use ($app, $appbox)
            {

              $feed = new \Feed_Adapter($appbox, $id);
              $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

              if (!$feed->is_owner($user))
                return $app->redirect('/admin/publications/feed/' . $id . '/?error=' . _('You are not the owner of this feed, you can not edit it'));

              $request = $app['request'];

              try
              {
                $collection = \collection::get_from_base_id($request->get('base_id'));
              }
              catch (\Exception $e)
              {
                $collection = null;
              }

              $feed->set_title($request->get('title'));
              $feed->set_subtitle($request->get('subtitle'));
              $feed->set_collection($collection);
              $feed->set_public($request->get('public'));

              return $app->redirect('/admin/publications/list/');
            });


    $controllers->post('/feed/{id}/iconupload/', function($id) use ($app, $appbox)
            {
              $feed = new \Feed_Adapter($appbox, $id);
              $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

              if (!$feed->is_owner($user))
                return new Response('ERROR:you are not allowed');

              if ($_FILES['Filedata']['error'] !== 0)
                return new Response('ERROR:error while upload');

              $file = new \system_file($_FILES['Filedata']['tmp_name']);
              if (!in_array($file->get_mime(), array('image/jpeg', 'image/jpg', 'image/gif')))
                return new Response('ERROR:bad filetype');

              if ($file->getSize() > 200000)
                return new Response('ERROR:file too large');

              $datas = $file->get_technical_datas();
              if (!isset($datas[\system_file::TC_DATAS_WIDTH]) || !isset($datas[\system_file::TC_DATAS_HEIGHT]))
                return new Response('ERROR:file is not square');

              if ($datas[\system_file::TC_DATAS_WIDTH] != $datas[\system_file::TC_DATAS_HEIGHT])
                return new Response('ERROR:file is not square');

              $feed->set_icon($file);
              unlink($file->getPathname());

              return new Response('FILEHREF:' . $feed->get_icon_url() . '?' . mt_rand(100000, 999999));
            });

    $controllers->post('/feed/{id}/addpublisher/', function($id) use ($app, $appbox)
            {
              $error = '';
              try
              {
                $request = $app['request'];
                $user = \User_Adapter::getInstance($request->get('usr_id'), $appbox);
                $feed = new \Feed_Adapter($appbox, $id);
                $feed->add_publisher($user);
              }
              catch (\Exception $e)
              {
                $error = $e->getMessage();
              }

              return $app->redirect('/admin/publications/feed/' . $id . '/');
            });


    $controllers->post('/feed/{id}/removepublisher/', function($id) use ($app, $appbox)
            {
              try
              {
                $request = $app['request'];

                $feed = new \Feed_Adapter($appbox, $id);
                $publisher = new \Feed_Publisher_Adapter($appbox, $request->get('publisher_id'));
                $user = $publisher->get_user();
                if ($feed->is_publisher($user) === true && $feed->is_owner($user) === false)
                  $publisher->delete();
              }
              catch (\Exception $e)
              {
                $error = $e->getMessage();
              }

              return $app->redirect('/admin/publications/feed/' . $id . '/?err=' . $error);
            });

    $controllers->post('/feed/{id}/delete/', function($id) use ($app, $appbox)
            {
              $feed = new \Feed_Adapter($appbox, $id);
              $feed->delete();

              return $app->redirect('/admin/publications/list/');
            })->assert('id', '\d+');

    return $controllers;
  }

}
