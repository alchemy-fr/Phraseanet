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

use Alchemy\Phrasea\Cache\Manager as CacheManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CacheServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {


        $app['phraseanet.cache-service'] = $app->share(function(Application $app) {
                if ( ! file_exists(__DIR__ . '/../../../../../tmp/cache_registry.yml')) {
                    touch(__DIR__ . '/../../../../../tmp/cache_registry.yml');
                }

                return new CacheManager($app, new \SplFileInfo(__DIR__ . '/../../../../../tmp/cache_registry.yml'));
            });

        $app['cache'] = $app->share(function(Application $app) {

                return $app['phraseanet.cache-service']
                        ->get('MainCache', $app['phraseanet.configuration']->getCache())
                        ->getDriver();
            });

        $app['opcode-cache'] = $app->share(function(Application $app) {

                return $app['phraseanet.cache-service']
                        ->get('OpcodeCache', $app['phraseanet.configuration']->getOpcodeCache())
                        ->getDriver();
            });
    }

    public function boot(Application $app)
    {

    }
}
