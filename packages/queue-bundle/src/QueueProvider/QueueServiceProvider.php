<?php

namespace Alchemy\QueueProvider;

use Alchemy\Queue\MessageQueueRegistry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

class QueueServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Container $app)
    {
        $app['alchemy_queues.registry'] = function () {
            return new MessageQueueRegistry();
        };
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
        foreach ($app['alchemy_queues.queues'] as $name => $configuration) {
            /** @var MessageQueueRegistry $targetRegistry */
            $targetRegistry = $app['alchemy_queues.registry'];

            if (isset($configuration['registry'])) {
                $targetRegistry = $app[$configuration['registry']];
            }

            $targetRegistry->bindConfiguration($name, $configuration);
        }
    }
}
