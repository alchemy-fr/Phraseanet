<?php

namespace Alchemy\Queue\Amqp;

use Alchemy\Queue\Message;
use Alchemy\Queue\MessageHandlerResolver;
use Alchemy\Queue\MessageHandlingException;
use Alchemy\Queue\MessagePublishingException;
use Alchemy\Queue\MessageQueue;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;

class AmqpMessageQueue implements MessageQueue, LoggerAwareInterface
{
    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\AMQPExchange $exchange, \AMQPQueue $queue)
    {
        $this->exchange = $exchange;
        $this->queue = $queue;
        $this->logger = new NullLogger();
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Message $message
     */
    public function publish(Message $message)
    {
        $this->logger->debug('Publishing message to queue: ' . $this->queue->getName());

        $attributes = ['correlation_id' => $message->getCorrelationId()];
        try {
            $this->exchange->publish($message->getBody(), $this->queue->getName(), AMQP_DURABLE, $attributes);
        } catch (\AMQPException $exception) {
            throw new MessagePublishingException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @param MessageHandlerResolver $resolver
     */
    public function handle(MessageHandlerResolver $resolver)
    {
        $this->logger->debug('Consuming messages from AMQP queue: ' . $this->queue->getName());

        $this->queue->consume(function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($resolver) {
            $message = new Message($envelope->getBody(), $envelope->getCorrelationId());
            $handler = $resolver->resolveHandler($message);

            try {
                $this->logger->debug('Dispatching message to handler');
                $handler->handle($message);
                $this->ackMessage($queue, $envelope);
            } catch (MessageHandlingException $exception) {
                $this->logger->error('Caught exception while handling message: ' . $exception->getMessage());
                $this->nackMessage($queue, $envelope);
            }

            return false;
        }, AMQP_NOPARAM, Uuid::uuid4()->toString());
    }

    private function ackMessage(\AMQPQueue $queue, \AMQPEnvelope $envelope)
    {
        $this->logger->debug('ACK message', [
            'correlation_id' => $envelope->getCorrelationId(),
            'body' => $envelope->getBody()
        ]);

        $queue->ack($envelope->getDeliveryTag(), AMQP_NOPARAM);
    }

    private function nackMessage(\AMQPQueue $queue, \AMQPEnvelope $envelope)
    {
        $this->logger->debug('NACK message', [
            'correlation_id' => $envelope->getCorrelationId(),
            'body' => $envelope->getBody()
        ]);

        $queue->nack($envelope->getDeliveryTag(), AMQP_NOPARAM);
    }
}
