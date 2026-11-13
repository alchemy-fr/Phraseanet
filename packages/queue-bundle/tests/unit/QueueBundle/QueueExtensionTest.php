<?php

namespace Alchemy\QueueBundle\Tests;

use Alchemy\QueueBundle\DependencyInjection\QueueExtension;
use Alchemy\QueueBundle\Queue\QueueRegistry;
use Alchemy\QueueBundle\QueueBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class QueueExtensionTest extends TestCase
{
    public function testBundleProvidesTheExtension()
    {
        $bundle = new QueueBundle();

        $this->assertInstanceOf(QueueExtension::class, $bundle->getContainerExtension());
    }

    public function testQueuesAreBoundToTheRegistry()
    {
        $container = new ContainerBuilder();
        $extension = new QueueExtension();
        $extension->load([[
            'logger' => 'logger',
            'queues' => [
                'default_queue' => ['host' => 'localhost'],
            ],
        ]], $container);

        $definition = $container->getDefinition('alchemy_queues.registry');

        $this->assertSame(QueueRegistry::class, $definition->getClass());
        $this->assertEquals([
            ['setLogger', [new Reference('logger')]],
            ['bindConfiguration', ['default_queue', [
                'host' => 'localhost',
                'registry' => null,
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'password' => 'guest',
                'timeout' => 0,
            ]]],
        ], $definition->getMethodCalls());
    }
}
