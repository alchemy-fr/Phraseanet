<?php

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class WorkerConfigurationServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['alchemy_queues.queues'] = $app->share(function (Application $app) {
            return [
                'worker-queue' => [
                    'registry' => 'alchemy_worker.queue_registry',
                    'host' => 'localhost',
                    'port' => 5672,
                    'user' => 'guest',
                    'vhost' => '/'
                ]
            ];
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}
