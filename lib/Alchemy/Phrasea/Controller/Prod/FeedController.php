<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\FirewallAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\FeedEntryEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\Link\AggregateLinkGenerator;
use Alchemy\Phrasea\Feed\Link\FeedLinkGenerator;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Repositories\FeedEntryRepository;
use Alchemy\Phrasea\Model\Repositories\FeedItemRepository;
use Alchemy\Phrasea\Model\Repositories\FeedPublisherRepository;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FeedController extends Controller
{
    use DispatcherAware;
    use FirewallAware;

    public function publishRecordsAction(Request $request)
    {
        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());
        $publishing = RecordsRequest::fromRequest($this->app, $request, true, [], [\ACL::BAS_CHUPUB]);

        return $this->render(
            'prod/actions/publish/publish.html.twig',
            ['publishing' => $publishing, 'feeds' => $feeds]
        );
    }

    public function createFeedEntryAction(Request $request) {
        $feed = $this->getFeedRepository()->find($request->request->get('feed_id'));

        if (null === $feed) {
            $this->app->abort(404, "Feed not found");
        }

        $user = $this->getAuthenticatedUser();
        $publisher = $this->getFeedPublisherRepository()->findOneBy([
            'feed' => $feed, 
            'user' => $user,
        ]);

        if ('' === $title = trim($request->request->get('title', ''))) {
            $this->app->abort(400, "Bad request");
        }

        if (!$feed->isPublisher($user)) {
            $this->app->abort(403, 'Unauthorized action');
        }

        $entry = new FeedEntry();
        $entry->setAuthorEmail($request->request->get('author_mail'))
            ->setAuthorName($request->request->get('author_name'))
            ->setTitle($title)
            ->setFeed($feed)
            ->setPublisher($publisher)
            ->setSubtitle($request->request->get('subtitle', ''));

        $feed->addEntry($entry);

        $publishing = RecordsRequest::fromRequest($this->app, $request, true, [], [\ACL::BAS_CHUPUB]);
        $manager = $this->getEntityManager();
        foreach ($publishing as $record) {
            $item = new FeedItem();
            $item->setEntry($entry)
                ->setRecordId($record->getRecordId())
                ->setSbasId($record->getDataboxId());
            $entry->addItem($item);
            $manager->persist($item);
        }

        $manager->persist($entry);
        $manager->persist($feed);
        $manager->flush();

        $this->dispatch(PhraseaEvents::FEED_ENTRY_CREATE, new FeedEntryEvent(
            $entry, $request->request->get('notify')
        ));

        return $this->app->json(['error' => false, 'message' => false]);
    }

    public function editEntryAction($id) {
        $entry = $this->getFeedEntryRepository()->find($id);

        if (!$entry->isPublisher($this->getAuthenticatedUser())) {
            throw new AccessDeniedHttpException();
        }

        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());

        return $this->renderResponse(
            'prod/actions/publish/publish_edit.html.twig',
            ['entry' => $entry, 'feeds' => $feeds]
        );
    }

    public function updateEntryAction(Request $request, $id) {
        $entry = $this->getFeedEntryRepository()->find($id);

        if (null === $entry) {
            $this->app->abort(404, 'Entry not found');
        }
        if (!$entry->isPublisher($this->getAuthenticatedUser())) {
            $this->app->abort(403, 'Unathorized action');
        }
        if ('' === $title = trim($request->request->get('title', ''))) {
            $this->app->abort(400, "Bad request");
        }

        $entry
            ->setAuthorEmail($request->request->get('author_mail'))
            ->setAuthorName($request->request->get('author_name'))
            ->setTitle($title)
            ->setSubtitle($request->request->get('subtitle', ''))
        ;

        $current_feed_id = $entry->getFeed()->getId();
        $new_feed_id = $request->request->get('feed_id', $current_feed_id);
        if ($current_feed_id !== (int)$new_feed_id) {
            $new_feed = $this->getFeedRepository()->find($new_feed_id);

            if ($new_feed === null) {
                $this->app->abort(404, 'Feed not found');
            }

            if (!$new_feed->isPublisher($this->getAuthenticatedUser())) {
                $this->app->abort(403, 'You are not publisher of this feed');
            }
            $entry->setFeed($new_feed);
        }

        $items = explode(';', $request->request->get('sorted_lst'));

        $item_repository = $this->getFeedItemRepository();
        $manager = $this->getEntityManager();
        foreach ($items as $item_sort) {
            $item_sort_data = explode('_', $item_sort);
            if (count($item_sort_data) != 2) {
                continue;
            }
            $item = $item_repository->find($item_sort_data[0]);
            $item->setOrd($item_sort_data[1]);
            $manager->persist($item);
        }

        $manager->persist($entry);
        $manager->flush();

        return $this->app->json([
            'error'   => false,
            'message' => 'succes',
            'datas'   => $this->render('prod/results/entry.html.twig', ['entry' => $entry]),
        ]);
    }

    public function deleteEntryAction($id) {
        $entry = $this->getFeedEntryRepository()->find($id);

        if (null === $entry) {
            $this->app->abort(404, 'Entry not found');
        }
        if (!$entry->isPublisher($this->getAuthenticatedUser()) && $entry->getFeed()
                ->isOwner($this->getAuthenticatedUser()) === false
        ) {
            $this->app->abort(403, $this->app->trans('Action Forbidden : You are not the publisher'));
        }

        $manager = $this->getEntityManager();
        $manager->remove($entry);
        $manager->flush();

        return $this->app->json(['error' => false, 'message' => 'success']);
    }

    public function indexAction(Request $request) {
        $page = (int)$request->query->get('page');
        $page = $page > 0 ? $page : 1;

        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());

        return $this->renderResponse('prod/results/feeds.html.twig', [
            'feeds' => $feeds,
            'feed'  => new Aggregate($this->getEntityManager(), $feeds),
            'page'  => $page,
        ]);
    }

    public function showAction(Request $request, $id) {
        $page = (int)$request->query->get('page');
        $page = $page > 0 ? $page : 1;

        $feed = $this->getFeedRepository()->find($id);
        if (!$feed->isAccessible($this->getAuthenticatedUser(), $this->app)) {
            $this->app->abort(404, 'Feed not found');
        }
        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());

        return $this->renderResponse('prod/results/feeds.html.twig', [
            'feed'  => $feed,
            'feeds' => $feeds,
            'page'  => $page,
        ]);
    }

    public function subscribeAggregatedFeedAction(Request $request) {
        $renew = ($request->query->get('renew') === 'true');

        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());

        $link = $this->getAggregateLinkGenerator()->generate(
            new Aggregate($this->getEntityManager(), $feeds),
            $this->getAuthenticatedUser(),
            AggregateLinkGenerator::FORMAT_RSS,
            null,
            $renew
        );

        return $this->app->json([
            'texte' => '<p>' . $this->app->trans(
                    'publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.'
                ) . '</p><p>' . $this->app->trans('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
                <div><input type="text" readonly="readonly" class="input_select_copy" value="' . $link->getURI()
                . '"/></div>',
            'titre' => $this->app->trans('publications::votre rss personnel'),
        ]);
    }

    public function subscribeFeedAction(Request $request, $id) {
        $renew = ($request->query->get('renew') === 'true');

        $feed = $this->getFeedRepository()->find($id);
        if (!$feed->isAccessible($this->getAuthenticatedUser(), $this->app)) {
            $this->app->abort(404, 'Feed not found');
        }
        $link = $this->getUserLinkGenerator()->generate(
            $feed,
            $this->getAuthenticatedUser(),
            FeedLinkGenerator::FORMAT_RSS,
            null,
            $renew
        );

        return $this->app->json([
            'texte' => '<p>' . $this->app->trans(
                    'publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.'
                ) . '</p><p>' . $this->app->trans('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
                <div><input type="text" style="width:100%" value="' . $link->getURI() . '"/></div>',
            'titre' => $this->app->trans('publications::votre rss personnel')
        ]);
    }

    public function ensureUserHasPublishRight()
    {
        $this->requireRight(\ACL::BAS_CHUPUB);
    }

    /**
     * @return FeedRepository
     */
    private function getFeedRepository()
    {
        return $this->app['repo.feeds'];
    }

    /**
     * @return FeedPublisherRepository
     */
    private function getFeedPublisherRepository()
    {
        return $this->app['repo.feed-publishers'];
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->app['orm.em'];
    }

    /**
     * @return FeedEntryRepository
     */
    private function getFeedEntryRepository()
    {
        return $this->app['repo.feed-entries'];
    }

    /**
     * @return FeedItemRepository
     */
    private function getFeedItemRepository()
    {
        return $this->app['repo.feed-items'];
    }

    /**
     * @return AggregateLinkGenerator
     */
    private function getAggregateLinkGenerator()
    {
        return $this->app['feed.aggregate-link-generator'];
    }

    /**
     * @return FeedLinkGenerator
     */
    private function getUserLinkGenerator()
    {
        return $this->app['feed.user-link-generator'];
    }
}
