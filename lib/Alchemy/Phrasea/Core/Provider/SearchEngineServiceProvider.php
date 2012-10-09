<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Service\Builder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchEngineServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['phraseanet.SE'] = $app->share(function($app) {
            $configuration = $app['phraseanet.configuration']
                ->getService($app['phraseanet.configuration']
                ->getSearchEngine());

            $service = Builder::create($app, $configuration);

            return $service->getDriver();
        });
    }

    public function boot(Application $app)
    {
    }
}
