<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Caption;

use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Databox\ClosureDataboxBoundRepositoryFactory;
use Alchemy\Phrasea\Databox\DataboxBoundRepositoryProvider;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Record\RecordReference;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CaptionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['provider.factory.caption'] = $app->protect(function ($databoxId) use ($app) {
            return function ($recordId, array $data) use ($app, $databoxId) {
                $recordReference = RecordReference::createFromDataboxIdAndRecordId($databoxId, $recordId);

                return new \caption_record($app, $recordReference, $data);
            };
        });

        $app['provider.data_repo.caption'] = $app->share(function (Application $app) {
            return new DataboxBoundRepositoryProvider(new CaptionDataRepositoryFactory(
                new DataboxConnectionProvider($app['phraseanet.appbox']),
                $app['cache']
            ));
        });

        $app['provider.repo.caption'] = $app->share(function (Application $app) {
            return new DataboxBoundRepositoryProvider(
                new ClosureDataboxBoundRepositoryFactory(function ($databoxId) use ($app) {
                    /** @var CaptionDataRepository $dataRepository */
                    $dataRepository = $app['provider.data_repo.caption']->getRepositoryForDatabox($databoxId);
                    $captionFactoryProvider = $app['provider.factory.caption'];

                    return new CaptionRepository(
                        $dataRepository,
                        $captionFactoryProvider($databoxId)
                    );
                })
            );
        });

        $app['service.caption'] = $app->share(function (Application $app) {
            return new CaptionService($app['provider.repo.caption']);
        });
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber(new CaptionCacheInvalider(new LazyLocator($app, 'provider.data_repo.caption')));
    }
}
