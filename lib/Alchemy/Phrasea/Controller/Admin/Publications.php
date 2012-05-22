<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Publications implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $appbox = \appbox::get_instance($app['Core']);
        $session = $appbox->get_session();

        $controllers = new ControllerCollection();

        $controllers->get('/list/', function() use ($app, $appbox) {
                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
                $feeds = \Feed_Collection::load_all($appbox, $user);

                $template = 'admin/publications/list.html';
                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return $twig->render($template, array('feeds' => $feeds));
            });


        $controllers->post('/create/', function() use ($app, $appbox) {

                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
                $request = $app['request'];

                $feed = \Feed_Adapter::create($appbox, $user, $request->get('title'), $request->get('subtitle'));

                if ($request->get('public') == '1')
                    $feed->set_public(true);
                elseif ($request->get('base_id'))
                    $feed->set_collection(\collection::get_from_base_id($request->get('base_id')));

                return $app->redirect('/admin/publications/list/');
            });


        $controllers->get('/feed/{id}/', function($id) use ($app, $appbox) {
                $feed = new \Feed_Adapter($appbox, $id);

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return $twig->render('admin/publications/fiche.html'
                        , array(
                        'feed'  => $feed
                        , 'error' => $app['request']->get('error')
                        )
                );
            })->assert('id', '\d+');


        $controllers->post('/feed/{id}/update/', function($id) use ($app, $appbox) {

                $feed = new \Feed_Adapter($appbox, $id);
                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

                if ( ! $feed->is_owner($user)) {
                    return $app->redirect('/admin/publications/feed/' . $id . '/?error=' . _('You are not the owner of this feed, you can not edit it'));
                }

                $request = $app['request'];

                try {
                    $collection = \collection::get_from_base_id($request->get('base_id'));
                } catch (\Exception $e) {
                    $collection = null;
                }

                $feed->set_title($request->get('title'));
                $feed->set_subtitle($request->get('subtitle'));
                $feed->set_collection($collection);
                $feed->set_public($request->get('public'));

                return $app->redirect('/admin/publications/list/');
            })->assert('id', '\d+');


        $controllers->post('/feed/{id}/iconupload/', function($id) use ($app, $appbox) {
                $feed = new \Feed_Adapter($appbox, $id);
                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

                if ( ! $feed->is_owner($user)) {
                    return new Response('ERROR:you are not allowed');
                }

                $request = $app["request"];

                $fileData = $request->files->get("Filedata");

                if ($fileData['error'] !== 0) {
                    return new Response('ERROR:error while upload');
                }

                $media = \MediaVorus\MediaVorus::guess(new \SplFileInfo($fileData['tmp_name']));

                if ($media->getType() !== \MediaVorus\Media\Media::TYPE_IMAGE) {

                    return new Response('ERROR:bad filetype');
                }

                $spec = new \MediaAlchemyst\Specification\Image();

                $spec->setResizeMode(\MediaAlchemyst\Specification\Image::RESIZE_MODE_OUTBOUND);
                $spec->setDimensions(32, 32);
                $spec->setStrip(true);
                $spec->setQuality(72);

                $tmpname = tempnam(sys_get_temp_dir(), 'feed_icon');

                try {
                    $app['Core']['media-alchemyst']
                        ->open($media->getFile()->getPathname())
                        ->turnInto($tmpname, $spec)
                        ->close();
                } catch (\MediaAlchemyst\Exception\Exception $e) {
                    return new Response('Error while handling icon');
                }

                $feed->set_icon($tmpname);

                unset($media);

                unlink($tmpname);
                unlink($fileData['tmp_name']);

                return new Response('FILEHREF:' . $feed->get_icon_url() . '?' . mt_rand(100000, 999999));
            })->assert('id', '\d+');

        $controllers->post('/feed/{id}/addpublisher/', function($id) use ($app, $appbox) {
                $error = '';
                try {
                    $request = $app['request'];
                    $user = \User_Adapter::getInstance($request->get('usr_id'), $appbox);
                    $feed = new \Feed_Adapter($appbox, $id);
                    $feed->add_publisher($user);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }

                return $app->redirect('/admin/publications/feed/' . $id . '/?err=' . $error);
            })->assert('id', '\d+');


        $controllers->post('/feed/{id}/removepublisher/', function($id) use ($app, $appbox) {
                try {
                    $request = $app['request'];

                    $feed = new \Feed_Adapter($appbox, $id);
                    $publisher = new \Feed_Publisher_Adapter($appbox, $request->get('publisher_id'));
                    $user = $publisher->get_user();
                    if ($feed->is_publisher($user) === true && $feed->is_owner($user) === false)
                        $publisher->delete();
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }

                return $app->redirect('/admin/publications/feed/' . $id . '/?err=' . $error);
            })->assert('id', '\d+');

        $controllers->post('/feed/{id}/delete/', function($id) use ($app, $appbox) {
                $feed = new \Feed_Adapter($appbox, $id);
                $feed->delete();

                return $app->redirect('/admin/publications/list/');
            })->assert('id', '\d+');

        return $controllers;
    }
}
