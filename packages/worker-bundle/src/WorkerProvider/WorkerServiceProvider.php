<?php

namespace Alchemy\WorkerProvider;

use Alchemy\Queue\MessageQueueRegistry;
use Alchemy\Worker\MessageDispatcher;
use Alchemy\Worker\ProcessPool;
use Alchemy\Worker\TypeBasedWorkerResolver;
use Alchemy\Worker\WorkerInvoker;
use Alchemy\WorkerBundle\Commands\DispatchingConsumerCommand;
use Alchemy\WorkerBundle\Commands\InvokeWorkerCommand;
use Alchemy\WorkerBundle\Commands\ShowQueueConfigurationCommand;
use Psr\Log\LoggerAwareInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class WorkerServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Container $app)
    {
        $this->registerDefaultParameters($app);

        $loggerSetter = function (LoggerAwareInterface $loggerAware) use ($app) {
            if (isset($app[$app['alchemy_worker.logger_service_name']])) {
                $loggerAware->setLogger($app[$app['alchemy_worker.logger_service_name']]);
            }

            return $loggerAware;
        };

        $app['alchemy_worker.process_pool'] = function (Container $app) use ($loggerSetter) {
            return $loggerSetter(new ProcessPool($app['alchemy_worker.process_pool_size']));
        };

        $app['alchemy_worker.worker_invoker'] = function (Container $app) use ($loggerSetter) {
            return $loggerSetter(new WorkerInvoker($app['alchemy_worker.process_pool']));
        };

        $app['alchemy_worker.queue_registry'] = function () use ($loggerSetter) {
            return $loggerSetter(new MessageQueueRegistry());
        };

        $app['alchemy_worker.message_dispatcher'] = function (Container $app) use ($loggerSetter) {
            return $loggerSetter(new MessageDispatcher(
                $app['alchemy_worker.worker_invoker'],
                $app['alchemy_worker.queue_registry'],
                $app['alchemy_worker.queue_name']
            ));
        };

        $app['alchemy_worker.type_based_worker_resolver'] = function () {
            return new TypeBasedWorkerResolver();
        };

        $app['alchemy_worker.worker_resolver'] = function (Container $app) {
            return $app['alchemy_worker.type_based_worker_resolver'];
        };

        $app['alchemy_worker.commands.run_dispatcher_command'] = function (Container $app) {
            return new DispatchingConsumerCommand(
                $app['alchemy_worker.message_dispatcher'],
                $app['alchemy_worker.worker_invoker']
            );
        };

        $app['alchemy_worker.commands.run_worker_command'] = function (Container $app) {
            return new InvokeWorkerCommand($app['alchemy_worker.worker_resolver']);
        };

        $app['alchemy_worker.commands.show_configuration'] = function (Container $app) {
            return new ShowQueueConfigurationCommand($app['alchemy_worker.queue_registry']);
        };
    }

    /**
     * @param Container $app
     */
    protected function registerDefaultParameters(Container $app)
    {
        if (!isset($app['alchemy_worker.logger_service_name'])) {
            $app['alchemy_worker.logger_service_name'] = 'logger';
        }

        if (!isset($app['alchemy_worker.queue_name'])) {
            $app['alchemy_worker.queue_name'] = 'alchemy_worker';
        }

        if (!isset($app['alchemy_worker.process_pool_size'])) {
            $app['alchemy_worker.process_pool_size'] = 8;
        }
    }
}
