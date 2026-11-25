<?php

namespace Alchemy\Queue\Tests;

use Alchemy\Queue\MessageQueue;
use Alchemy\Queue\MessageQueueRegistry;

class MessageQueueRegistryTest extends \PHPUnit\Framework\TestCase
{
    use RequiresAmqpServer;

    public function testRegistryIsInitiallyEmpty()
    {
        $registry = new MessageQueueRegistry();

        $this->assertFalse($registry->hasQueue('named-queue'), 'Registry should not contain a queue named "named-queue"');
    }

    public function testQueuesCanBeRegisteredUsingConfigArray()
    {
        self::skipIfAmqpServerIsUnavailable();

        $registry = new MessageQueueRegistry();

        $registry->bindConfiguration('default-queue', self::getAmqpServerParameters());

        $this->assertTrue($registry->hasQueue('default-queue'));
        $this->assertInstanceOf(MessageQueue::class, $registry->getQueue('default-queue'));
    }
}
