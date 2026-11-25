<?php

namespace Alchemy\Queue\Tests;

use Alchemy\Queue\Message;
use Alchemy\Queue\MessageHandler;
use Alchemy\Queue\MessageHandlerResolver;

class MessageHandlerResolverTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    public function testHandlerCanBeAddedToResolver()
    {
        $handler = $this->prophesize(MessageHandler::class)->reveal();
        $nextHandler = $this->prophesize(MessageHandler::class)->reveal();
        $resolver = new MessageHandlerResolver();

        $resolver->addHandler($handler);

        $this->assertEquals([$handler], $resolver->getHandlers());

        $resolver->addHandler($nextHandler);

        $this->assertEquals([$handler, $nextHandler], $resolver->getHandlers());
    }

    public function testHandlerCanBeResolvedByMessage()
    {
        $handler = $this->prophesize(MessageHandler::class);

        $resolver = new MessageHandlerResolver();
        $message = new Message('mock-body');

        $handler->accepts($message)->willReturn(true);
        $resolver->addHandler($handler->reveal());

        $this->assertEquals($handler->reveal(), $resolver->resolveHandler($message));
    }

    public function testResolvingByUnsupportedMessageFallbackToDefaultResolver()
    {
        $default = $this->prophesize(MessageHandler::class)->reveal();
        $handler = $this->prophesize(MessageHandler::class);

        $resolver = new MessageHandlerResolver($default);
        $message = new Message('mock-body');

        $handler->accepts($message)->willReturn(false);
        $resolver->addHandler($handler->reveal());

        $this->assertSame($default, $resolver->resolveHandler($message));
        $this->assertNotSame($handler->reveal(), $resolver->resolveHandler($message));
    }
}
