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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Publications implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/list/', function(PhraseaApplication $app) {

                $feeds = \Feed_Collection::load_all(
                        $app['phraseanet.appbox'], $app['phraseanet.core']->getAuthenticatedUser()
                );

                return $app['twig']
                        ->render('admin/publications/list.html', array('feeds' => $feeds));
            });

        $controllers->post('/create/', function(PhraseaApplication $app, Request $request) {

                $user = $app['phraseanet.core']->getAuthenticatedUser();

                $feed = \Feed_Adapter::create(
                        $app['phraseanet.appbox'], $user, $request->get('title'), $request->get('subtitle')
                );

                if ($request->get('public') == '1') {
                    $feed->set_public(true);
                } elseif ($request->get('base_id')) {
                    $feed->set_collection(\collection::get_from_base_id($request->get('base_id')));
                }

                return $app->redirect('/admin/publications/list/');
            });

        $controllers->get('/feed/{id}/', function(PhraseaApplication $app, Request $request, $id) {
                $feed = new \Feed_Adapter($app['phraseanet.appbox'], $id);

                return $app['twig']
                        ->render('admin/publications/fiche.html.twig', array('feed'  => $feed, 'error' => $app['request']->get('error')));
            })->assert('id', '\d+');

        $controllers->post('/feed/{id}/update/', function(PhraseaApplication $app, Request $request, $id) {

                    $feed = new \Feed_Adapter($app['phraseanet.appbox'], $id);

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
                })->before(function(Request $request) use ($app) {
                    $feed = new \Feed_Adapter($app['phraseanet.appbox'], $request->get('id'));

                    if ( ! $feed->is_owner($app['phraseanet.core']->getAuthenticatedUser())) {
                        return $app->redirect('/admin/publications/feed/' . $request->get('id') . '/?error=' . _('You are not the owner of this feed, you can not edit it'));
                    }
                })
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/iconupload/', function(PhraseaApplication $app, Request $request, $id) {
                $datas = array(
                    'success' => false,
                    'message' => '',
                );

                $feed = new \Feed_Adapter($app['phraseanet.appbox'], $id);

                $user = $app['phraseanet.core']->getAuthenticatedUser();

                $request = $app["request"];

                if ( ! $feed->is_owner($user)) {
                    $datas['message'] = 'You are not allowed to do that';

                    return $app->json($datas);
                }

                try {
                    if ( ! $request->files->get('files')) {
                        throw new \Exception_BadRequest('Missing file parameter');
                    }

                    if (count($request->files->get('files')) > 1) {
                        throw new \Exception_BadRequest('Upload is limited to 1 file per request');
                    }

                    $file = current($request->files->get('files'));

                    if ( ! $file->isValid()) {
                        throw new \Exception_BadRequest('Uploaded file is invalid');
                    }

                    $media = $app['phraseanet.core']['mediavorus']->guess($file);

                    if ($media->getType() !== \MediaVorus\Media\Media::TYPE_IMAGE) {
                        throw new \Exception_BadRequest('Bad filetype');
                    }

                    $spec = new \MediaAlchemyst\Specification\Image();

                    $spec->setResizeMode(\MediaAlchemyst\Specification\Image::RESIZE_MODE_OUTBOUND);
                    $spec->setDimensions(32, 32);
                    $spec->setStrip(true);
                    $spec->setQuality(72);

                    $tmpname = tempnam(sys_get_temp_dir(), 'feed_icon');

                    try {
                        $app['phraseanet.core']['media-alchemyst']
                            ->open($media->getFile()->getPathname())
                            ->turnInto($tmpname, $spec)
                            ->close();
                    } catch (\MediaAlchemyst\Exception\Exception $e) {
                        throw new \Exception_InternalServerError('Error while resizing');
                    }

                    $feed->set_icon($tmpname);

                    unset($media);

                    $app['phraseanet.core']['file-system']->remove($tmpname);

                    $datas['success'] = true;
                } catch (\Exception $e) {
                    $datas['message'] = _('Unable to add file to Phraseanet');
                }

                return $app->json($datas);
            })->assert('id', '\d+');

        $controllers->post('/feed/{id}/addpublisher/', function(PhraseaApplication $app, $id) {
                $error = '';
                try {
                    $request = $app['request'];
                    $user = \User_Adapter::getInstance($request->get('usr_id'), $app['phraseanet.appbox']);
                    $feed = new \Feed_Adapter($app['phraseanet.appbox'], $id);
                    $feed->add_publisher($user);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }

                return $app->redirect('/admin/publications/feed/' . $id . '/?err=' . $error);
            })->assert('id', '\d+');

        $controllers->post('/feed/{id}/removepublisher/', function(PhraseaApplication $app, $id) {
                try {
                    $request = $app['request'];

                    $feed = new \Feed_Adapter($app['phraseanet.appbox'], $id);
                    $publisher = new \Feed_Publisher_Adapter($app['phraseanet.appbox'], $request->get('publisher_id'));
                    $user = $publisher->get_user();
                    if ($feed->is_publisher($user) === true && $feed->is_owner($user) === false)
                        $publisher->delete();
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }

                return $app->redirect('/admin/publications/feed/' . $id . '/?err=' . $error);
            })->assert('id', '\d+');

        $controllers->post('/feed/{id}/delete/', function(PhraseaApplication $app, $id) {
                $feed = new \Feed_Adapter($app['phraseanet.appbox'], $id);
                $feed->delete();

                return $app->redirect('/admin/publications/list/');
            })->assert('id', '\d+');

        return $controllers;
    }
}
