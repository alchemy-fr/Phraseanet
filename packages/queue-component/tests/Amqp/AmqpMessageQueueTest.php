<?php

namespace Alchemy\Queue\Tests\Amqp;

use Alchemy\Queue\Amqp\AmqpMessageQueue;
use Alchemy\Queue\Message;
use Alchemy\Queue\MessageHandler;
use Alchemy\Queue\MessageHandlerResolver;
use Alchemy\Queue\MessageHandlingException;
use Alchemy\Queue\MessagePublishingException;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class AmqpMessageQueueTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;

    public function testMessagesArePublishedInExchange()
    {
        /** @var \AMQPExchange $exchange */
        $exchange = $this->prophesize(\AMQPExchange::class);
        /** @var \AMQPQueue $queue */
        $queue = $this->prophesize(\AMQPQueue::class);

        $queue->getName()->willReturn('mock-queue');
        $exchange->publish('mock-body', 'mock-queue', Argument::type('int'), ['correlation_id' => 'mock-id'])
            ->shouldBeCalled();

        $messageQueue = new AmqpMessageQueue($exchange->reveal(), $queue->reveal());
        $message = new Message('mock-body', 'mock-id');

        $messageQueue->publish($message);
    }

    public function testQueueThrowsExceptionWhenPublishingFails()
    {
        /** @var \AMQPExchange $exchange */
        $exchange = $this->prophesize(\AMQPExchange::class);
        /** @var \AMQPQueue $queue */
        $queue = $this->prophesize(\AMQPQueue::class);

        $queue->getName()->willReturn('mock-queue');
        $exchange->publish('mock-body', 'mock-queue', Argument::type('int'), ['correlation_id' => 'mock-id'])
            ->willThrow(new \AMQPExchangeException('Publishing failed.'));

        $messageQueue = new AmqpMessageQueue($exchange->reveal(), $queue->reveal());
        $message = new Message('mock-body', 'mock-id');

        $this->expectException(MessagePublishingException::class);

        $messageQueue->publish($message);
    }

    public function testHandleInvokesAvailableHandlerAndAcknowledgesMessage()
    {
        $resolver = new MessageHandlerResolver();

        $exchange = $this->prophesize(\AMQPExchange::class);
        $queue = $this->prophesize(\AMQPQueue::class);
        /** @var \AMQPEnvelope $envelope */
        $envelope = $this->prophesize(\AMQPEnvelope::class);
        $handler = $this->prophesize(MessageHandler::class);

        $resolver->addHandler($handler->reveal());

        $handler->accepts(Argument::type(Message::class))->willReturn(true);
        $handler->handle(Argument::type(Message::class))->shouldBeCalled();

        $envelope->getBody()->willReturn('mock-body');
        $envelope->getCorrelationId()->willReturn('mock-correlation-id');
        $envelope->getDeliveryTag()->willReturn(42);

        $queue->ack(42, Argument::type('int'))->shouldBeCalled();
        $queue->nack(42, Argument::type('int'))->shouldNotBeCalled();
        $queue->getName()->willReturn('mock-queue');
        $queue->consume(Argument::type(\Closure::class), Argument::any(), Argument::any())->will(function ($args) use ($envelope, $queue) {
            $args[0]($envelope->reveal(), $queue->reveal());
        });

        $messageQueue = new AmqpMessageQueue($exchange->reveal(), $queue->reveal());

        $messageQueue->handle($resolver);
    }

    public function testHandleInvokesAvailableHandler()
    {
        $resolver = new MessageHandlerResolver();

        $exchange = $this->prophesize(\AMQPExchange::class);
        $queue = $this->prophesize(\AMQPQueue::class);
        /** @var \AMQPEnvelope $envelope */
        $envelope = $this->prophesize(\AMQPEnvelope::class);
        $handler = $this->prophesize(MessageHandler::class);

        $resolver->addHandler($handler->reveal());

        $handler->accepts(Argument::type(Message::class))->willReturn(true);
        $handler->handle(Argument::type(Message::class))->shouldBeCalled()->willThrow(new MessageHandlingException());

        $envelope->getBody()->willReturn('mock-body');
        $envelope->getCorrelationId()->willReturn('mock-correlation-id');
        $envelope->getDeliveryTag()->willReturn(42);

        $queue->nack(42, Argument::type('int'))->shouldBeCalled();
        $queue->ack(42, Argument::type('int'))->shouldNotBeCalled();
        $queue->getName()->willReturn('mock-queue');
        $queue->consume(Argument::type(\Closure::class), Argument::any(), Argument::any())->will(function ($args) use ($envelope, $queue) {
            $args[0]($envelope->reveal(), $queue->reveal());
        });

        $messageQueue = new AmqpMessageQueue($exchange->reveal(), $queue->reveal());

        $messageQueue->handle($resolver);
    }
}
