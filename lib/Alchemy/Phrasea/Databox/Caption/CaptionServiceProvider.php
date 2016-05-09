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
            return function (array $data) use ($app, $databoxId) {
                $recordReference = RecordReference::createFromDataboxIdAndRecordId($databoxId, $data['record_id']);

                return new \caption_record($app, $recordReference, $data);
            };
        });

        $app['provider.repo.caption'] = $app->share(function (Application $app) {
            $connectionProvider = new DataboxConnectionProvider($app['phraseanet.appbox']);
            $factoryProvider = $app['provider.factory.caption'];

            $repositoryFactory = new CaptionRepositoryFactory($connectionProvider, $app['cache'], $factoryProvider);

            return new DataboxBoundRepositoryProvider($repositoryFactory);
        });

        $app['service.caption'] = $app->share(function (Application $app) {
            return new CaptionService($app['provider.repo.caption']);
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
