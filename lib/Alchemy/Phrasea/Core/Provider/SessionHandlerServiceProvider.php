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

use Alchemy\Phrasea\Core\Configuration\SessionHandlerFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SessionHandlerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['session.storage.handler.factory'] = $app->share(function (Application $app) {
            return new SessionHandlerFactory($app['cache.connection-factory']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
