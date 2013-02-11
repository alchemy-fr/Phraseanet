<?php

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
                $app['phraseanet.registry']->get('GV_homeTitle', 'Phraseanet'),
                $app['phraseanet.registry']->get('GV_defaulmailsenderaddr', 'no-reply@phraseanet.com')
            );
        });

        $app['notification.prefix'] = $app->share(function (Application $app) {
            return $app['phraseanet.registry']->get('GV_email_prefix');
        });

        $app['notification.deliverer'] = $app->share(function($app) {
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
