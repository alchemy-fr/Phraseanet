<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Subdef;

use Alchemy\Phrasea\Databox\DataboxBoundRepositoryProvider;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Record\RecordReference;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MediaSubdefServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['provider.factory.media_subdef'] = $app->protect(function ($databoxId) use ($app) {
            return function (array $data) use ($app, $databoxId) {
                $recordReference = RecordReference::createFromDataboxIdAndRecordId($databoxId, $data['record_id']);

                return new \media_subdef($app, $recordReference, $data['name'], false, $data);
            };
        });

        $app['provider.repo.media_subdef'] = $app->share(function (Application $app) {
            $connectionProvider = new DataboxConnectionProvider($app['phraseanet.appbox']);
            $factoryProvider = $app['provider.factory.media_subdef'];

            $repositoryFactory = new MediaSubdefRepositoryFactory($connectionProvider, $app['cache'], $factoryProvider);

            return new DataboxBoundRepositoryProvider($repositoryFactory);
        });

        $app['service.media_subdef'] = $app->share(function (Application $app) {
            return new MediaSubdefService($app['provider.repo.media_subdef']);
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
