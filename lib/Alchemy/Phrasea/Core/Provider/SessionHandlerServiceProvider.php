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
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SessionHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['session.storage.handler.factory'] = function ($app) {
            return new SessionHandlerFactory($app['cache.connection-factory']);
        };
    }
}
