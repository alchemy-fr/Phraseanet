<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Media\Factory\DbalRepositoryFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TechnicalDataServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['service.technical_data'] = $app->share(function (Application $app) {
            $connectionProvider = new DataboxConnectionProvider($app['phraseanet.appbox']);
            $repositoryFactory = new DbalRepositoryFactory($connectionProvider);

            return new TechnicalDataService(new RecordTechnicalDataSetRepositoryProvider($repositoryFactory));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
