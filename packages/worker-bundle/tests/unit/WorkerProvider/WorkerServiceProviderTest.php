<?php

namespace Alchemy\WorkerProvider\Tests;

use Alchemy\Queue\MessageQueueRegistry;
use Alchemy\Worker\MessageDispatcher;
use Alchemy\Worker\ProcessPool;
use Alchemy\Worker\TypeBasedWorkerResolver;
use Alchemy\Worker\WorkerInvoker;
use Alchemy\WorkerBundle\Commands\DispatchingConsumerCommand;
use Alchemy\WorkerBundle\Commands\InvokeWorkerCommand;
use Alchemy\WorkerBundle\Commands\ShowQueueConfigurationCommand;
use Alchemy\WorkerProvider\WorkerServiceProvider;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\NullLogger;

class WorkerServiceProviderTest extends TestCase
{
    public function testDefaultParametersAreRegistered()
    {
        $app = new Container();
        $app->register(new WorkerServiceProvider());

        $this->assertSame('logger', $app['alchemy_worker.logger_service_name']);
        $this->assertSame('alchemy_worker', $app['alchemy_worker.queue_name']);
        $this->assertSame(8, $app['alchemy_worker.process_pool_size']);
    }

    public function testDefinedParametersAreKept()
    {
        $app = new Container();
        $app['alchemy_worker.queue_name'] = 'my-queue';
        $app['alchemy_worker.process_pool_size'] = 2;
        $app->register(new WorkerServiceProvider());

        $this->assertSame('my-queue', $app['alchemy_worker.queue_name']);
        $this->assertSame(2, $app['alchemy_worker.process_pool_size']);
    }

    public function testServicesAreRegistered()
    {
        $app = new Container();
        $app['logger'] = function () {
            return new NullLogger();
        };
        $app->register(new WorkerServiceProvider());

        $this->assertInstanceOf(ProcessPool::class, $app['alchemy_worker.process_pool']);
        $this->assertInstanceOf(WorkerInvoker::class, $app['alchemy_worker.worker_invoker']);
        $this->assertInstanceOf(MessageQueueRegistry::class, $app['alchemy_worker.queue_registry']);
        $this->assertInstanceOf(MessageDispatcher::class, $app['alchemy_worker.message_dispatcher']);
        $this->assertInstanceOf(TypeBasedWorkerResolver::class, $app['alchemy_worker.type_based_worker_resolver']);
        $this->assertSame($app['alchemy_worker.type_based_worker_resolver'], $app['alchemy_worker.worker_resolver']);
        $this->assertSame($app['alchemy_worker.queue_registry'], $app['alchemy_worker.queue_registry']);
        $this->assertInstanceOf(DispatchingConsumerCommand::class, $app['alchemy_worker.commands.run_dispatcher_command']);
        $this->assertInstanceOf(InvokeWorkerCommand::class, $app['alchemy_worker.commands.run_worker_command']);
        $this->assertInstanceOf(ShowQueueConfigurationCommand::class, $app['alchemy_worker.commands.show_configuration']);
    }
}
