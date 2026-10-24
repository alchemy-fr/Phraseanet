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

use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\Notification\Emitter;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class NotificationDelivererServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['notification.default.emitter'] = function (Application $app) {
            return new Emitter(
                $app['conf']->get(['registry', 'general', 'title']),
                $app['conf']->get(['registry', 'email', 'emitter-email'], 'no-reply@phraseanet.com')
            );
        };

        $app['notification.prefix'] = function (Application $app) {
            return $app['conf']->get(['registry', 'email', 'prefix']);
        };

        $app['notification.deliverer'] = function ($app) {
            return new Deliverer(
                $app['mailer'],
                $app['dispatcher'],
                $app['notification.default.emitter'],
                $app['notification.prefix']
            );
        };
    }
}
