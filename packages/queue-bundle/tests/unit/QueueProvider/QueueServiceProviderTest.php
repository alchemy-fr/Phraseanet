<?php

namespace Alchemy\QueueProvider\Tests;

use Alchemy\Queue\MessageQueueRegistry;
use Alchemy\QueueProvider\QueueServiceProvider;
use PHPUnit\Framework\TestCase;
use Silex\Application;

class QueueServiceProviderTest extends TestCase
{
    public function testRegistryIsRegistered()
    {
        $app = new Application();
        $app->register(new QueueServiceProvider());

        $this->assertInstanceOf(MessageQueueRegistry::class, $app['alchemy_queues.registry']);
        $this->assertSame($app['alchemy_queues.registry'], $app['alchemy_queues.registry']);
    }

    public function testQueuesAreBoundToTheirRegistryOnBoot()
    {
        $app = new Application();
        $app->register(new QueueServiceProvider());

        $app['custom.registry'] = function () {
            return new MessageQueueRegistry();
        };
        $app['alchemy_queues.queues'] = [
            'default-queue' => ['host' => 'localhost'],
            'custom-queue' => ['host' => 'localhost', 'registry' => 'custom.registry'],
        ];

        $app->boot();

        $this->assertSame(['default-queue' => ['host' => 'localhost']], $app['alchemy_queues.registry']->getConfigurations());
        $this->assertSame(
            ['custom-queue' => ['host' => 'localhost', 'registry' => 'custom.registry']],
            $app['custom.registry']->getConfigurations()
        );
    }
}
