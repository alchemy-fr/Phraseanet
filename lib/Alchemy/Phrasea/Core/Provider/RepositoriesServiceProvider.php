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
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\User');
        };
        $app['repo.auth-failures'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\AuthFailure');
        };
        $app['repo.sessions'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Session');
        };
        $app['repo.tasks'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Task');
        };
        $app['repo.registrations'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Registration');
        };
        $app['repo.baskets'] = function (PhraseaApplication $app) {
            /** @var BasketRepository $repository */
            $repository = $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Basket');
            $repository->setTranslator($app['translator']);

            return $repository;
        };
        $app['repo.basket-elements'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\BasketElement');
        };
        $app['repo.basket-participants'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\BasketParticipant');
        };
        $app['repo.story-wz'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\StoryWZ');
        };
        $app['repo.orders'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Order');
        };
        $app['repo.order-elements'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\OrderElement');
        };
        $app['repo.feeds'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Feed');
        };
        $app['repo.feed-entries'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\FeedEntry');
        };
        $app['repo.feed-items'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\FeedItem');
        };
        $app['repo.feed-publishers'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\FeedPublisher');
        };
        $app['repo.feed-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\FeedToken');
        };
        $app['repo.aggregate-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken');
        };
        $app['repo.usr-lists'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');
        };
        $app['repo.usr-list-owners'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UsrListOwner');
        };
        $app['repo.usr-list-entries'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UsrListEntry');
        };
        $app['repo.lazaret-files'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\LazaretFile');
        };
        $app['repo.usr-auth-providers'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');
        };
        $app['repo.ftp-exports'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\FtpExport');
        };
        $app['repo.user-queries'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\UserQuery');
        };
        $app['repo.tokens'] = function ($app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Token');
        };
        $app['repo.presets'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Preset');
        };
        $app['repo.api-accounts'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiAccount');
        };
        $app['repo.api-logs'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiLog');
        };
        $app['repo.api-applications'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiApplication');
        };
        $app['repo.api-oauth-codes'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiOauthCode');
        };
        $app['repo.api-oauth-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiOauthToken');
        };
        $app['repo.api-oauth-refresh-tokens'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiOauthRefreshToken');
        };
        $app['repo.webhook-event'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\WebhookEvent');
        };
        $app['repo.webhook-delivery'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\WebhookEventDelivery');
        };
        $app['repo.worker-running-job'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\WorkerRunningJob');
        };
        $app['repo.worker-job'] = function (PhraseaApplication $app) {
            return $app['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\WorkerJob');
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
