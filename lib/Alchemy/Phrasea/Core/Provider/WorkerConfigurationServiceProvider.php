<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Worker\CallableWorkerFactory;
use Alchemy\Worker\TypeBasedWorkerResolver;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class WorkerConfigurationServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Container $app)
    {
        // Define the first defined queue as the worker queue
        $app['alchemy_worker.queue_name'] = function (Application $app) {
            $queues = $app['alchemy_queues.queues'];

            reset($queues);

            return key($queues);
        };

        $app['alchemy_queues.queues'] = function (Application $app) {
            $defaultConfiguration = [
                'worker-queue' => [
                    'registry'  => 'alchemy_worker.queue_registry',
                    'host'      => 'localhost',
                    'port'      => 5672,
                    'user'      => 'guest',
                    'password'  => 'guest',
                    'vhost'     => '/',
                    'heartbeat' => 60,
                ]
            ];

            try {
                /** @var PropertyAccess $configuration */
                $configuration = $app['conf'];

                $queueConfigurations = $configuration->get(['workers', 'queue'], $defaultConfiguration);

                $config = [];

                foreach($queueConfigurations as $name => $queueConfiguration) {
                    $queueKey = $name;

                    if (! isset($queueConfiguration['name'])) {
                        if (! is_string($queueKey)) {
                            throw new \RuntimeException('Invalid queue configuration: configuration has no key or name.');
                        }

                        $queueConfiguration['name'] = $queueKey;
                    }

                    $config[$queueConfiguration['name']] = $queueConfiguration ;
                }

                return $config;
            }
            catch (RuntimeException $exception) {
                return [];
            }
        };
    }
}
