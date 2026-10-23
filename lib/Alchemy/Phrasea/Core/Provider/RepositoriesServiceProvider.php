<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Collection\CollectionFactory;
use Alchemy\Phrasea\Collection\CollectionRepositoryRegistry;
use Alchemy\Phrasea\Collection\Factory\ArrayCachedCollectionRepositoryFactory;
use Alchemy\Phrasea\Collection\Factory\CachedCollectionRepositoryFactory;
use Alchemy\Phrasea\Collection\Factory\DbalCollectionRepositoryFactory;
use Alchemy\Phrasea\Collection\Reference\ArrayCacheCollectionReferenceRepository;
use Alchemy\Phrasea\Collection\Reference\DbalCollectionReferenceRepository;
use Alchemy\Phrasea\Databox\ArrayCacheDataboxRepository;
use Alchemy\Phrasea\Databox\CachingDataboxRepositoryDecorator;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DbalDataboxRepository;
use Alchemy\Phrasea\Databox\Field\DataboxFieldFactory;
use Alchemy\Phrasea\Databox\Field\DbalDataboxFieldRepository;
use Alchemy\Phrasea\Databox\Record\LegacyRecordRepository;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class RepositoriesServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        if (!$app instanceof PhraseaApplication) {
            throw new \LogicException('Expects $app to be an instance of Phraseanet application');
        }

        $app['repo.users'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:User');
        };
        $app['repo.auth-failures'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:AuthFailure');
        };
        $app['repo.sessions'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:Session');
        };
        $app['repo.tasks'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:Task');
        };
        $app['repo.registrations'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:Registration');
        };
        $app['repo.baskets'] = function (PhraseaApplication $app) {
            /** @var BasketRepository $repository */
            $repository = $app['orm.em']->getRepository('Phraseanet:Basket');
            $repository->setTranslator($app['translator']);

            return $repository;
        };
        $app['repo.basket-elements'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:BasketElement');
        };
        $app['repo.basket-participants'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:BasketParticipant');
        };
        $app['repo.story-wz'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:StoryWZ');
        };
        $app['repo.orders'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:Order');
        };
        $app['repo.order-elements'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:OrderElement');
        };
        $app['repo.feeds'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:Feed');
        };
        $app['repo.feed-entries'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:FeedEntry');
        };
        $app['repo.feed-items'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:FeedItem');
        };
        $app['repo.feed-publishers'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:FeedPublisher');
        };
        $app['repo.feed-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:FeedToken');
        };
        $app['repo.aggregate-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:AggregateToken');
        };
        $app['repo.usr-lists'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:UsrList');
        };
        $app['repo.usr-list-owners'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:UsrListOwner');
        };
        $app['repo.usr-list-entries'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:UsrListEntry');
        };
        $app['repo.lazaret-files'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:LazaretFile');
        };
        $app['repo.usr-auth-providers'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:UsrAuthProvider');
        };
        $app['repo.ftp-exports'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:FtpExport');
        };
        $app['repo.user-queries'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:UserQuery');
        };
        $app['repo.tokens'] = function ($app) {
            return $app['orm.em']->getRepository('Phraseanet:Token');
        };
        $app['repo.presets'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:Preset');
        };
        $app['repo.api-accounts'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:ApiAccount');
        };
        $app['repo.api-logs'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:ApiLog');
        };
        $app['repo.api-applications'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:ApiApplication');
        };
        $app['repo.api-oauth-codes'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:ApiOauthCode');
        };
        $app['repo.api-oauth-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:ApiOauthToken');
        };
        $app['repo.api-oauth-refresh-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:ApiOauthRefreshToken');
        };
        $app['repo.webhook-event'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:WebhookEvent');
        };
        $app['repo.webhook-delivery'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:WebhookEventDelivery');
        };
        $app['repo.worker-running-job'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:WorkerRunningJob');
        };
        $app['repo.worker-job'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Phraseanet:WorkerJob');
        };

        $app['repo.databoxes'] = function (PhraseaApplication $app) {
            $appbox = $app->getApplicationBox();

            $factory = new DataboxFactory($app);
            $repository = new CachingDataboxRepositoryDecorator(
                new DbalDataboxRepository($appbox->get_connection(), $factory),
                $app['cache'],
                $appbox->get_cache_key($appbox::CACHE_LIST_BASES),
                $factory
            );

            $repository = new ArrayCacheDataboxRepository($repository);

            $factory->setDataboxRepository($repository);

            return $repository;
        };

        $app['repo.fields.factory'] = $app->protect(function (\databox $databox) use ($app) {
            return new DbalDataboxFieldRepository($databox->get_connection(), new DataboxFieldFactory($app, $databox));
        });

        $app['repo.records.factory'] = $app->protect(function (\databox $databox) use ($app) {
            return new LegacyRecordRepository($app, $databox, $app['conf']->get(['main', 'key']));
        });
	
        $app['repo.collection-references'] = function (PhraseaApplication $app) {
            $repository = new DbalCollectionReferenceRepository($app->getApplicationBox()->get_connection());

            return new ArrayCacheCollectionReferenceRepository($repository);
        };

        $app['repo.collections-registry'] = function (PhraseaApplication $app) {
            $factory = new CollectionFactory($app);
            $connectionProvider = new DataboxConnectionProvider($app->getApplicationBox());

            $repositoryFactory = new DbalCollectionRepositoryFactory(
                $connectionProvider,
                $factory,
                $app['repo.collection-references']
            );

            $repositoryFactory = new CachedCollectionRepositoryFactory(
                $app,
                $repositoryFactory,
                $app['cache'],
                'phrasea.collections'
            );

            $repositoryFactory = new ArrayCachedCollectionRepositoryFactory($repositoryFactory);

            return new CollectionRepositoryRegistry($app, $repositoryFactory, $app['repo.collection-references']);
        };
    }
}
