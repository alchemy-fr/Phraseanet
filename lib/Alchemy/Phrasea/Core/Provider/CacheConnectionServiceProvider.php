<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Cache\ConnectionFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class CacheConnectionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['cache.connection-factory'] = $app->share(function () {
            return new ConnectionFactory();
        });
    }

    public function boot(Application $app)
    {
    }
}
