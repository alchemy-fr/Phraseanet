<?php

namespace Alchemy\Queue\Amqp;

use Alchemy\Queue\MessageQueue;
use Alchemy\Queue\MessageQueueFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AmqpMessageQueueFactory implements MessageQueueFactory, LoggerAwareInterface
{
    /**
     * @param array $configuration
     * @param LoggerInterface $logger
     * @return AmqpMessageQueueFactory
     */
    public static function create(array $configuration = [], ?LoggerInterface $logger = null)
    {
        return new self(AmqpConfiguration::parse($configuration), $logger);
    }

    /**
     * @var AmqpConfiguration
     */
    private $configuration;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var \AMQPChannel
     */
    private $channel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(?AmqpConfiguration $configuration = null, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration ?: new AmqpConfiguration();
        $this->logger = $logger ?: new NullLogger();
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
     * @param $name
     * @return MessageQueue
     */
    public function getNamedQueue($name)
    {
        $exchange = $this->declareExchange();

        return $this->buildQueue($exchange, $name, $this->configuration->getDeadLetterExchange());
    }

    /**
     * @param \AMQPExchange $exchange
     * @param $queueName
     * @param string $errorExchangeName
     * @return \AMQPQueue
     */
    private function buildQueue(\AMQPExchange $exchange, $queueName, $errorExchangeName = '')
    {
        $queue = new \AMQPQueue($exchange->getChannel());
        $queue->setName($queueName);
        $queue->setFlags(AMQP_DURABLE);

        if (trim((string) $errorExchangeName) != '') {
            $queue->setArgument('x-dead-letter-exchange', $errorExchangeName);
        }

        $queue->declareQueue();
        $queue->bind($exchange->getName(), $queueName);

        $messageQueue = new AmqpMessageQueue($exchange, $queue);

        $messageQueue->setLogger($this->logger);

        return $messageQueue;
    }


    protected function declareExchange()
    {
        if ($this->exchange == null) {
            $this->exchange = new \AMQPExchange($this->getChannel());

            $this->exchange->setFlags(AMQP_DURABLE);

            if ($this->configuration->getExchange() != null) {
                $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
                $this->exchange->setName($this->configuration->getExchange());
                $this->exchange->declareExchange();
            }
        }

        return $this->exchange;
    }

    /**
     * @return \AMQPConnection
     */
    public function getConnection()
    {
        if ($this->connection == null) {
            $this->connection = new \AMQPConnection($this->configuration->toConnectionArray());

            if ($this->configuration->getTimeout() > 0) {
                $this->connection->setReadTimeout($this->configuration->getTimeout());
            }

            $this->connection->connect();
        }

        return $this->connection;
    }

    /**
     * @return \AMQPChannel
     */
    public function getChannel()
    {
        if ($this->channel == null) {
            $this->channel = new \AMQPChannel($this->getConnection());
        }

        return $this->channel;
    }
}
