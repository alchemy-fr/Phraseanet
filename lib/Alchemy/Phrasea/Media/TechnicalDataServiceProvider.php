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
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class TechnicalDataServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['service.technical_data'] = function (Application $app) {
            $connectionProvider = new DataboxConnectionProvider($app['phraseanet.appbox']);
            $repositoryFactory = new DbalRepositoryFactory($connectionProvider);

            return new TechnicalDataService(new RecordTechnicalDataSetRepositoryProvider($repositoryFactory));
        };
    }
}
