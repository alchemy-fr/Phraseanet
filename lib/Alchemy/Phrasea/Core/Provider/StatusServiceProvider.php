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

use Alchemy\Phrasea\Status\CacheStatusStructureProvider;
use Alchemy\Phrasea\Status\StatusStructureFactory;
use Alchemy\Phrasea\Status\XmlStatusStructureProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class StatusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['status.provider'] = $app->share(function() use ($app) {
            return new CacheStatusStructureProvider(
                $app['cache'],
                new XmlStatusStructureProvider($app['root.path'], $app['locales.available'])
            );
        });

        $app['factory.status-structure'] = $app->share(function() use ($app) {
            return new StatusStructureFactory($app['status.provider']);
        });
    }

    public function boot(Application $app)
    {
    }
}
