<?php

namespace Alchemy\Worker\Tests;

use Alchemy\Queue\Message;
use Alchemy\Queue\MessageQueueRegistry;
use Alchemy\Worker\MessageDispatcher;
use Alchemy\Worker\ProcessPool;
use Alchemy\Worker\WorkerInvoker;
use PHPUnit\Framework\TestCase;

class MessageDispatcherTest extends TestCase
{
    public function testAcceptsOnlyTypedJsonMessages()
    {
        $dispatcher = new MessageDispatcher(new WorkerInvoker(new ProcessPool()), new MessageQueueRegistry(), 'queue');

        $this->assertTrue($dispatcher->accepts(Message::fromArray(['message_type' => 'type', 'payload' => []])));
        $this->assertFalse($dispatcher->accepts(Message::fromArray(['payload' => []])));
        $this->assertFalse($dispatcher->accepts(new Message('not json')));
    }

    public function testHandleInvokesTheWorker()
    {
        $invoker = $this->getMockBuilder(WorkerInvoker::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['invokeWorker'])
            ->getMock();
        $invoker->expects($this->once())
            ->method('invokeWorker')
            ->with('type', json_encode(['key' => 'value']));

        $dispatcher = new MessageDispatcher($invoker, new MessageQueueRegistry(), 'queue');
        $dispatcher->handle(Message::fromArray(['message_type' => 'type', 'payload' => ['key' => 'value']]));
    }
}
