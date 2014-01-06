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
use Silex\Application;
use Silex\ServiceProviderInterface;

class NotificationDelivererServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['notification.default.emitter'] = $app->share(function (Application $app) {
            return new Emitter(
                $app['conf']->get(['registry', 'general', 'title']),
                $app['conf']->get(['registry', 'email', 'emitter-email'], 'no-reply@phraseanet.com')
            );
        });

        $app['notification.prefix'] = $app->share(function (Application $app) {
            return $app['conf']->get(['registry', 'email', 'prefix']);
        });

        $app['notification.deliverer'] = $app->share(function ($app) {
            return new Deliverer(
                $app['mailer'],
                $app['dispatcher'],
                $app['notification.default.emitter'],
                $app['notification.prefix']
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
