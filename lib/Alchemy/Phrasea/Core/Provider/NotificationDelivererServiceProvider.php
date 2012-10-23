<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Notification\Deliverer;
use Silex\Application;
use Silex\ServiceProviderInterface;

class NotificationDelivererServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['notification.deliverer'] = $app->share(function($app) {
            return new Deliverer($app['mailer'], $app['phraseanet.registry']);
        });
    }

    public function boot(Application $app)
    {
    }
}
