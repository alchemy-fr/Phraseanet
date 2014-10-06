<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\FeedEntryEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\Link\AggregateLinkGenerator;
use Alchemy\Phrasea\Feed\Link\FeedLinkGenerator;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Feed implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.feed'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->post('/requestavailable/', function (Application $app, Request $request) {
            $feeds = $app['repo.feeds']->getAllForUser(
                $app['acl']->get($app['authentication']->getUser())
            );
            $publishing = RecordsRequest::fromRequest($app, $request, true, [], ['bas_chupub']);

            return $app['twig']->render('prod/actions/publish/publish.html.twig', ['publishing' => $publishing, 'feeds' => $feeds]);
        });

        $controllers->post('/entry/create/', function (Application $app, Request $request) {
            $feed = $app['repo.feeds']->find($request->request->get('feed_id'));

            if (null === $feed) {
                $app->abort(404, "Feed not found");
            }

            $publisher = $app['repo.feed-publishers']->findOneBy(['feed' => $feed, 'user' => $app['authentication']->getUser()]);

            if ('' === $title = trim($request->request->get('title', ''))) {
                $app->abort(400, "Bad request");
            }

            if (!$feed->isPublisher($app['authentication']->getUser())) {
                $app->abort(403, 'Unathorized action');
            }

            $entry = new FeedEntry();
            $entry->setAuthorEmail($request->request->get('author_mail'))
                ->setAuthorName($request->request->get('author_name'))
                ->setTitle($title)
                ->setFeed($feed)
                ->setPublisher($publisher)
                ->setSubtitle($request->request->get('subtitle', ''));

            $feed->addEntry($entry);

            $publishing = RecordsRequest::fromRequest($app, $request, true, [], ['bas_chupub']);
            foreach ($publishing as $record) {
                $item = new FeedItem();
                $item->setEntry($entry)
                    ->setRecordId($record->get_record_id())
                    ->setSbasId($record->get_sbas_id());
                $entry->addItem($item);
                $app['EM']->persist($item);
            }

            $app['EM']->persist($entry);
            $app['EM']->persist($feed);
            $app['EM']->flush();

            $app['dispatcher']->dispatch(PhraseaEvents::FEED_ENTRY_CREATE, new FeedEntryEvent($entry, $request->request->get('notify')));

            $datas = ['error' => false, 'message' => false];

            return $app->json($datas);
        })
            ->bind('prod_feeds_entry_create')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRight('bas_chupub');
            });

        $controllers->get('/entry/{id}/edit/', function (Application $app, Request $request, $id) {
            $entry = $app['repo.feed-entries']->find($id);

            if (!$entry->isPublisher($app['authentication']->getUser())) {
                throw new AccessDeniedHttpException();
            }

            $feeds = $app['repo.feeds']->getAllForUser($app['acl']->get($app['authentication']->getUser()));

            $datas = $app['twig']->render('prod/actions/publish/publish_edit.html.twig', ['entry' => $entry, 'feeds' => $feeds]);

            return new Response($datas);
        })
            ->bind('prod_feeds_entry_edit')
            ->assert('id', '\d+')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireRight('bas_chupub');
            });

        $controllers->post('/entry/{id}/update/', function (Application $app, Request $request, $id) {
            $datas = ['error' => true, 'message' => '', 'datas' => ''];
            $entry = $app['repo.feed-entries']->find($id);

            if (null === $entry) {
                $app->abort(404, 'Entry not found');
            }
            if (!$entry->isPublisher($app['authentication']->getUser())) {
                $app->abort(403, 'Unathorized action');
            }
            if ('' === $title = trim($request->request->get('title', ''))) {
                $app->abort(400, "Bad request");
            }

            $entry->setAuthorEmail($request->request->get('author_mail'))
                ->setAuthorName($request->request->get('author_name'))
                ->setTitle($title)
                ->setSubtitle($request->request->get('subtitle', ''));

            $currentFeedId = $entry->getFeed()->getId();
            $new_feed_id = $request->request->get('feed_id', $currentFeedId);
            if ($currentFeedId !== (int) $new_feed_id) {

                $new_feed = $app['repo.feeds']->find($new_feed_id);

                if ($new_feed === null) {
                    $app->abort(404, 'Feed not found');
                }

                if (!$new_feed->isPublisher($app['authentication']->getUser())) {
                    $app->abort(403, 'You are not publisher of this feed');
                }
                $entry->setFeed($new_feed);
            }

            $items = explode(';', $request->request->get('sorted_lst'));

            foreach ($items as $item_sort) {
                $item_sort_datas = explode('_', $item_sort);
                if (count($item_sort_datas) != 2) {
                    continue;
                }
                $item = $app['repo.feed-items']->find($item_sort_datas[0]);
                $item->setOrd($item_sort_datas[1]);
                $app['EM']->persist($item);
            }

            $app['EM']->persist($entry);
            $app['EM']->flush();

            return $app->json([
                'error' => false,
                'message' => 'succes',
                'datas' => $app['twig']->render('prod/feeds/entry.html.twig', [
                    'entry' => $entry
                ])
            ]);
        })
            ->bind('prod_feeds_entry_update')
            ->assert('id', '\d+')->before(function (Request $request) use ($app) {
                $app['firewall']->requireRight('bas_chupub');
            });

        $controllers->post('/entry/{id}/delete/', function (Application $app, Request $request, $id) {
            $datas = ['error' => true, 'message' => ''];

            $entry = $app['repo.feed-entries']->find($id);

            if (null === $entry) {
                $app->abort(404, 'Entry not found');
            }
            if (!$entry->isPublisher($app['authentication']->getUser()) && $entry->getFeed()->isOwner($app['authentication']->getUser()) === false) {
                $app->abort(403, $app->trans('Action Forbidden : You are not the publisher'));
            }

            $app['EM']->remove($entry);
            $app['EM']->flush();

            return $app->json(['error' => false, 'message' => 'succes']);
        })
            ->bind('prod_feeds_entry_delete')
            ->assert('id', '\d+')->before(function (Request $request) use ($app) {
                $app['firewall']->requireRight('bas_chupub');
            });

        $controllers->get('/', function (Application $app, Request $request) {
            $request = $app['request'];
            $page = (int) $request->query->get('page');
            $page = $page > 0 ? $page : 1;

            $feeds = $app['repo.feeds']->getAllForUser($app['acl']->get($app['authentication']->getUser()));

            $datas = $app['twig']->render('prod/feeds/feeds.html.twig', [
                'feeds' => $feeds,
                'feed' => new Aggregate($app['EM'], $feeds),
                'page' => $page
            ]);

            return new Response($datas);
        })->bind('prod_feeds');

        $controllers->get('/feed/{id}/', function (Application $app, Request $request, $id) {
            $page = (int) $request->query->get('page');
            $page = $page > 0 ? $page : 1;

            $feed = $app['repo.feeds']->find($id);
            if (!$feed->isAccessible($app['authentication']->getUser(), $app)) {
                $app->abort(404, 'Feed not found');
            }
            $feeds = $app['repo.feeds']->getAllForUser($app['acl']->get($app['authentication']->getUser()));

            $datas = $app['twig']->render('prod/feeds/feeds.html.twig', ['feed' => $feed, 'feeds' => $feeds, 'page' => $page]);

            return new Response($datas);
        })
            ->bind('prod_feeds_feed')
            ->assert('id', '\d+');

        $controllers->get('/subscribe/aggregated/', function (Application $app, Request $request) {
            $renew = ($request->query->get('renew') === 'true');

            $feeds = $app['repo.feeds']->getAllForUser($app['acl']->get($app['authentication']->getUser()));

            $link = $app['feed.aggregate-link-generator']->generate(new Aggregate($app['EM'], $feeds),
                $app['authentication']->getUser(),
                AggregateLinkGenerator::FORMAT_RSS,
                null, $renew
            );

            $output = [
                'texte' => '<p>' . $app->trans('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                . '</p><p>' . $app->trans('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
            <div><input type="text" readonly="readonly" class="input_select_copy" value="' . $link->getURI() . '"/></div>',
                'titre' => $app->trans('publications::votre rss personnel')
            ];

            return $app->json($output);
        })->bind('prod_feeds_subscribe_aggregated');

        $controllers->get('/subscribe/{id}/', function (Application $app, Request $request, $id) {
            $renew = ($request->query->get('renew') === 'true');

            $feed = $app['repo.feeds']->find($id);
            if (!$feed->isAccessible($app['authentication']->getUser(), $app)) {
                $app->abort(404, 'Feed not found');
            }
            $link = $app['feed.user-link-generator']->generate($feed, $app['authentication']->getUser(), FeedLinkGenerator::FORMAT_RSS, null, $renew);

            $output = [
                'texte' => '<p>' . $app->trans('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                . '</p><p>' . $app->trans('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
            <div><input type="text" style="width:100%" value="' . $link->getURI() . '"/></div>',
                'titre' => $app->trans('publications::votre rss personnel')
            ];

            return $app->json($output);
        })
            ->bind('prod_feeds_subscribe_feed')
            ->assert('id', '\d+');

        return $controllers;
    }
}
