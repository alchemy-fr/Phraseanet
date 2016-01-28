<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Webhook\EventProcessorFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class WebhookServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['webhook.processor_factory'] = $app->share(function ($app) {
            return new EventProcessorFactory($app);
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
