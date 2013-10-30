<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Publications implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $app['controller.admin.publications'] = $this;
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAccessToModule('admin')
                ->requireRight('bas_chupub');
        });

        $controllers->get('/list/', function(PhraseaApplication $app) {
            $feeds = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->getAllForUser(
                $app['authentication']->getUser()
            );

            return $app['twig']
                    ->render('admin/publications/list.html.twig', array('feeds' => $feeds));
        })->bind('admin_feeds_list');

        $controllers->post('/create/', function(PhraseaApplication $app, Request $request) {
            if ('' === $title = trim($request->request->get('title', ''))) {
                $app->abort(400, "Bad request");
            }

            $publisher = new FeedPublisher();

            $feed = new Feed();

            $publisher->setFeed($feed);
            $publisher->setUsrId($app['authentication']->getUser()->get_id());
            $publisher->setIsOwner(true);

            $feed->addPublisher($publisher);
            $feed->setTitle($title);
            $feed->setSubtitle($request->request->get('subtitle', ''));

            if ($request->request->get('public') == '1') {
                $feed->setIsPublic(true);
            } elseif ($request->request->get('base_id')) {
                $feed->setCollection(\collection::get_from_base_id($app, $request->request->get('base_id')));
            }

            $publisher->setFeed($feed);

            $app['EM']->persist($feed);
            $app['EM']->persist($publisher);

            $app['EM']->flush();

            return $app->redirectPath('admin_feeds_list');
        })->bind('admin_feeds_create');

        $controllers->get('/feed/{id}/', function(PhraseaApplication $app, Request $request, $id) {
            $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $id);

            return $app['twig']
                    ->render('admin/publications/fiche.html.twig', array('feed'  => $feed, 'error' => $app['request']->query->get('error')));
        })
            ->bind('admin_feeds_feed')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/update/', function(PhraseaApplication $app, Request $request, $id) {

           if ('' === $title = trim($request->request->get('title', ''))) {
                $app->abort(400, "Bad request");
            }

            $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $id);

            try {
                $collection = \collection::get_from_base_id($app, $request->request->get('base_id'));
            } catch (\Exception $e) {
                $collection = null;
            }
            $feed->setTitle($title);
            $feed->setSubtitle($request->request->get('subtitle', ''));
            $feed->setCollection($collection);
            $feed->setIsPublic('1' === $request->request->get('public'));
            $app['EM']->persist($feed);
            $app['EM']->flush();

            return $app->redirectPath('admin_feeds_list');
        })->before(function(Request $request) use ($app) {
            $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $request->attributes->get('id'));

            if (!$feed->isOwner($app['authentication']->getUser())) {
                return $app->redirectPath('admin_feeds_feed', array('id' => $request->attributes->get('id'), 'error' =>  _('You are not the owner of this feed, you can not edit it')));
            }
        })
            ->bind('admin_feeds_feed_update')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/iconupload/', function(PhraseaApplication $app, Request $request, $id) {
            $datas = array(
                'success' => false,
                'message' => '',
            );
            $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $id);

            if (null === $feed) {
                $app->abort(404, "Feed not found");
            }

            $request = $app["request"];

            if (!$feed->isOwner($app['authentication']->getUser())) {
                $app->abort(403, "Access Forbidden");
            }

            try {
                if (!$request->files->get('files')) {
                    throw new BadRequestHttpException('Missing file parameter');
                }

                if (count($request->files->get('files')) > 1) {
                    throw new BadRequestHttpException('Upload is limited to 1 file per request');
                }

                $file = current($request->files->get('files'));

                if (!$file->isValid()) {
                    throw new BadRequestHttpException('Uploaded file is invalid');
                }

                $media = $app['mediavorus']->guess($file->getPathname());

                if ($media->getType() !== \MediaVorus\Media\MediaInterface::TYPE_IMAGE) {
                    throw new BadRequestHttpException('Bad filetype');
                }

                $spec = new \MediaAlchemyst\Specification\Image();

                $spec->setResizeMode(\MediaAlchemyst\Specification\Image::RESIZE_MODE_OUTBOUND);
                $spec->setDimensions(32, 32);
                $spec->setStrip(true);
                $spec->setQuality(72);

                $tmpname = tempnam(sys_get_temp_dir(), 'feed_icon').'.png';

                try {
                    $app['media-alchemyst']->turnInto($media->getFile()->getPathname(), $tmpname, $spec);
                } catch (\MediaAlchemyst\Exception\ExceptionInterface $e) {
                    throw new \Exception_InternalServerError('Error while resizing');
                }

                unset($media);

                $feed->setIconUrl(true);
                $app['EM']->persist($feed);
                $app['EM']->flush();

                $app['filesystem']->copy($tmpname, $app['root.path'] . '/config/feed_' . $feed->getId() . '.jpg');
                $app['filesystem']->copy($tmpname, sprintf('%s/www/custom/feed_%d.jpg', $app['root.path'], $feed->getId()));

                $app['filesystem']->remove($tmpname);

                $datas['success'] = true;
            } catch (\Exception $e) {
                $datas['message'] = _('Unable to add file to Phraseanet');
            }

            return $app->json($datas);
        })
            ->bind('admin_feeds_feed_icon')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/addpublisher/', function(PhraseaApplication $app, $id) {
            $error = '';
            try {
                $request = $app['request'];
                $user = \User_Adapter::getInstance($request->request->get('usr_id'), $app);
                $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $id);

                $publisher = new FeedPublisher();
                $publisher->setUsrId($user->get_id());
                $publisher->setFeed($feed);

                $feed->addPublisher($publisher);

                $app['EM']->persist($feed);
                $app['EM']->persist($publisher);

                $app['EM']->flush();
            } catch (\Exception $e) {
                $error = "An error occured";
            }

            return $app->redirectPath('admin_feeds_feed', array('id' => $id, 'error' => $error));
        })
            ->bind('admin_feeds_feed_add_publisher')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/removepublisher/', function(PhraseaApplication $app, $id) {
            try {
                $request = $app['request'];

                $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $id);

                $publisher = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\FeedPublisher', $request->request->get('publisher_id'));
                if (null === $publisher) {
                    $app->abort(404, "Feed Publisher not found");
                }

                $user = $publisher->getUser($app);
                if ($feed->isPublisher($user) && !$feed->isOwner($user)) {
                    $feed->removePublisher($publisher);

                    $app['EM']->remove($publisher);
                    $app['EM']->flush();
                }
            } catch (\Exception $e) {
                $error = "An error occured";
            }

            return $app->redirectPath('admin_feeds_feed', array('id' => $id, 'error' => $error));
        })
            ->bind('admin_feeds_feed_remove_publisher')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/delete/', function(PhraseaApplication $app, $id) {
            $feed = $app["EM"]->find('Alchemy\Phrasea\Model\Entities\Feed', $id);

            if (null === $feed) {
                $app->abort(404);
            }

            if (true === $feed->getIconURL()) {
                unlink($app['root.path'] . '/config/feed_' . $feed->getId() . '.jpg');
                unlink('custom/feed_' . $feed->getId() . '.jpg');
            }

            $app['EM']->remove($feed);
            $app['EM']->flush();

            return $app->redirectPath('admin_feeds_list');
        })
            ->bind('admin_feeds_feed_delete')
            ->assert('id', '\d+');

        return $controllers;
    }
}
