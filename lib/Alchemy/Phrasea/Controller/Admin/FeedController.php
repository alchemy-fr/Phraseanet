<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Alchemy\Phrasea\Model\Repositories\FeedPublisherRepository;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Image;
use MediaVorus\Media\MediaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

// use Symfony\Component\Filesystem\Filesystem;

class FeedController extends Controller
{
    public function listFeedsAction()
    {
        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());

        return $this->render('admin/publications/list.html.twig', ['feeds' => $feeds]);
    }

    public function createAction(Request $request)
    {
        if ('' === $title = trim($request->request->get('title', ''))) {
            $this->app->abort(400, "Bad request");
        }

        $publisher = new FeedPublisher();

        $feed = new Feed();

        $publisher->setFeed($feed);
        $publisher->setUser($this->getAuthenticatedUser());
        $publisher->setIsOwner(true);

        $feed->addPublisher($publisher);
        $feed->setTitle($title);
        $feed->setSubtitle($request->request->get('subtitle', ''));

        if ($request->request->get('public') == '1') {
            $feed->setIsPublic(true);
        } elseif ($request->request->get('base_id')) {
            $feed->setCollection(\collection::getByBaseId($this->app, $request->request->get('base_id')));
        }

        $publisher->setFeed($feed);

        $manager = $this->getObjectManager();
        $manager->persist($feed);
        $manager->persist($publisher);

        $manager->flush();

        return $this->app->redirectPath('admin_feeds_list');
    }

    /**
     * @param Request $request
     * @param int     $id
     * @return string
     */
    public function showAction(Request $request, $id)
    {
        $feed = $this->getFeedRepository()->find($id);

        return $this->render('admin/publications/fiche.html.twig', [
            'feed'  => $feed,
            'error' => $request->query->get('error'),
        ]);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @return Response
     */
    function updateAction(Request $request, $id) {
        if ('' === $title = trim($request->request->get('title', ''))) {
            $this->app->abort(400, "Bad request");
        }

        $feedRepository = $this->getFeedRepository();
        /** @var Feed $feed */
        $feed = $feedRepository->find($id);
        if (!$feed->isOwner($this->getAuthenticatedUser())) {
            return $this->app->redirectPath('admin_feeds_feed', [
                'id'    => $request->attributes->get('id'),
                'error' => $this->app->trans('You are not the owner of this feed, you can not edit it'),
            ]);
        }

        try {
            $collection = \collection::getByBaseId($this->app, $request->request->get('base_id'));
        } catch (\Exception $e) {
            $collection = null;
        }
        $feed->setTitle($title);
        $feed->setSubtitle($request->request->get('subtitle', ''));
        $feed->setCollection($collection);
        $feed->setIsPublic('1' === $request->request->get('public'));

        $manager = $this->getObjectManager();
        $manager->persist($feed);
        $manager->flush();

        return $this->app->redirectPath('admin_feeds_list');
    }

    public function uploadIconAction(Request $request, $id) {
        $datas = [
            'success' => false,
            'message' => '',
        ];
        $feed = $this->getFeedRepository()->find($id);

        if (null === $feed) {
            $this->app->abort(404, "Feed not found");
        }

        if (!$feed->isOwner($this->getAuthenticatedUser())) {
            $this->app->abort(403, "Access Forbidden");
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

            $media = $this->app->getMediaFromUri($file->getPathname());

            if ($media->getType() !== MediaInterface::TYPE_IMAGE) {
                throw new BadRequestHttpException('Bad filetype');
            }

            $spec = new Image();

            $spec->setResizeMode(Image::RESIZE_MODE_OUTBOUND);
            $spec->setDimensions(32, 32);
            $spec->setStrip(true);
            $spec->setQuality(72);

            $tmpname = tempnam(sys_get_temp_dir(), 'feed_icon').'.png';

            try {
                /** @var Alchemyst $alchemyst */
                $alchemyst = $this->app['media-alchemyst'];
                $alchemyst->turnInto($media->getFile()->getPathname(), $tmpname, $spec);
            } catch (\MediaAlchemyst\Exception\ExceptionInterface $e) {
                throw new \Exception_InternalServerError('Error while resizing');
            }

            unset($media);

            $feed->setIconUrl(true);
            $manager = $this->getObjectManager();
            $manager->persist($feed);
            $manager->flush();

            /** @var Filesystem $filesystem */
            $filesystem = $this->app['filesystem'];
            $rootPath = $this->app['root.path'];
            $filesystem->copy($tmpname, $rootPath . '/config/feed_' . $feed->getId() . '.jpg');
            $filesystem->copy($tmpname, sprintf('%s/www/custom/feed_%d.jpg', $rootPath, $feed->getId()));

            $filesystem->remove($tmpname);

            $datas['success'] = true;
        } catch (\Exception $e) {
            $datas['message'] = $this->app->trans('Unable to add file to Phraseanet');
        }

        return $this->app->json($datas);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @return Response
     */
    public function addPublisherAction(Request $request, $id) {
        $error = '';
        try {
            /** @var UserRepository $userRepository */
            $userRepository = $this->app['repo.users'];
            $user = $userRepository->find($request->request->get('usr_id'));
            $feed = $this->getFeedRepository()->find($id);

            $publisher = new FeedPublisher();
            $publisher->setUser($user);
            $publisher->setFeed($feed);

            $feed->addPublisher($publisher);

            $manager = $this->getObjectManager();
            $manager->persist($feed);
            $manager->persist($publisher);

            $manager->flush();
        } catch (\Exception $e) {
            $error = "An error occured";
        }

        return $this->app->redirectPath('admin_feeds_feed', ['id' => $id, 'error' => $error]);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @return Response
     */
    public function removePublisherAction(Request $request, $id) {
        try {
            $feed = $this->getFeedRepository()->find($id);

            /** @var FeedPublisherRepository $publisherRepository */
            $publisherRepository = $this->app["repo.feed-publishers"];
            $publisher = $publisherRepository->find($request->request->get('publisher_id'));
            if (null === $publisher) {
                $this->app->abort(404, "Feed Publisher not found");
            }

            $user = $publisher->getUser();
            if ($feed->isPublisher($user) && !$feed->isOwner($user)) {
                $feed->removePublisher($publisher);

                $manager = $this->getObjectManager();
                $manager->remove($publisher);
                $manager->flush();
            }
        } catch (\Exception $e) {
            $error = "An error occured";
        }

        return $this->app->redirectPath('admin_feeds_feed', ['id' => $id, 'error' => $error]);
    }

    /**
     * @param int $id
     * @return Response
     */
    public function deleteAction($id) {
        $feed = $this->getFeedRepository()->find($id);

        if (null === $feed) {
            $this->app->abort(404);
        }

        if (true === $feed->getIconURL()) {
            unlink($this->app['root.path'] . '/config/feed_' . $feed->getId() . '.jpg');
            unlink('custom/feed_' . $feed->getId() . '.jpg');
        }

        $manager = $this->getObjectManager();
        $manager->remove($feed);
        $manager->flush();

        return $this->app->redirectPath('admin_feeds_list');
    }

    /**
     * @return FeedRepository
     */
    public function getFeedRepository()
    {
        return $this->app['repo.feeds'];
    }

    /**
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->app['orm.em'];
    }
}
