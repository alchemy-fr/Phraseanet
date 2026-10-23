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

use Alchemy\Phrasea\Cache\Manager as CacheManager;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Cache\Factory;
use Pimple\Container;

class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['phraseanet.cache-registry'] = function () use ($app) {
            return $app['cache.path'].'/cache_registry.php';
        };

        $app['phraseanet.cache-compiler'] = function () {
            return new Compiler();
        };

        $app['phraseanet.cache-factory'] = function (Application $app) {
            return new Factory($app['cache.connection-factory']);
        };

        $app['phraseanet.cache-service'] = function (Application $app) {
            return new CacheManager(
                $app['phraseanet.cache-compiler'],
                $app['phraseanet.cache-registry'],
                $app['monolog'],
                $app['phraseanet.cache-factory']
            );
        };

        $app['cache'] = function (Application $app) {
            $conf = $app['conf']->get(['main', 'cache']);

            return $app['phraseanet.cache-service']->factory('cache', $conf['type'], $conf['options']);
        };

        $app['opcode-cache'] = function (Application $app) {
            /** @var CacheManager $service */
            $service = $app['phraseanet.cache-service'];
            return $service->factory('opcode-cache', 'ArrayCache', []);
        };
    }
}
