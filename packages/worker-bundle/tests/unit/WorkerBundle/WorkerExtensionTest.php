<?php

namespace Alchemy\WorkerBundle\Tests;

use Alchemy\Queue\MessageQueueRegistry;
use Alchemy\WorkerBundle\DependencyInjection\Compiler\WorkerCompilerPass;
use Alchemy\WorkerBundle\DependencyInjection\WorkerExtension;
use Alchemy\WorkerBundle\WorkerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class WorkerExtensionTest extends TestCase
{
    public function testBundleProvidesTheExtension()
    {
        $bundle = new WorkerBundle();

        $this->assertInstanceOf(WorkerExtension::class, $bundle->getContainerExtension());
    }

    public function testServicesAreLoaded()
    {
        $container = new ContainerBuilder();
        $extension = new WorkerExtension();
        $extension->load([[
            'queues' => [
                'worker_queue' => ['host' => 'localhost', 'registry' => 'alchemy_worker.queue_registry'],
            ],
        ]], $container);

        $this->assertTrue($container->hasDefinition('alchemy_worker.message_dispatcher'));
        $this->assertTrue($container->hasDefinition('alchemy_worker.commands.run_worker_command'));
        $this->assertSame(MessageQueueRegistry::class, $container->getDefinition('alchemy_queues.registry')->getClass());

        $calls = $container->getDefinition('alchemy_worker.queue_registry')->getMethodCalls();
        $this->assertSame('bindConfiguration', $calls[1][0]);
        $this->assertSame('worker_queue', $calls[1][1][0]);
    }

    public function testCompilerPassRegistersTaggedFactories()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('alchemy_worker.type_based_worker_resolver', new Definition());
        $factory = new Definition();
        $factory->addTag('alchemy_worker.worker_factory', ['message_type' => 'my-type']);
        $container->setDefinition('my.factory', $factory);

        (new WorkerCompilerPass())->process($container);

        $this->assertEquals(
            [['setFactory', ['my-type', new Reference('my.factory')]]],
            $container->getDefinition('alchemy_worker.type_based_worker_resolver')->getMethodCalls()
        );
    }
}
