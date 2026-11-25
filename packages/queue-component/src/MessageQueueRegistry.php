<?php

/*
 * This file is part of alchemy/queue-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Queue;

use Alchemy\Queue\Amqp\AmqpMessageQueueFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MessageQueueRegistry implements LoggerAwareInterface
{
    /**
     * @var array
     */
    private $configurations = [];

    /**
     * @var MessageQueue[]
     */
    private $queues = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param string $queueName
     * @param array $configuration
     */
    public function bindConfiguration($queueName, array $configuration)
    {
        $this->configurations[$queueName] = $configuration;
    }

    public function getConfigurations()
    {
        return $this->configurations;
    }

    public function hasQueue($queueName)
    {
        return isset($this->queues[$queueName]) || isset($this->configurations[$queueName]);
    }

    /**
     * @param string $queueName
     * @return MessageQueue
     */
    public function getQueue($queueName)
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        }

        if (isset($this->configurations[$queueName])) {
            $queue = AmqpMessageQueueFactory::create(
                $this->configurations[$queueName],
                $this->logger
            )->getNamedQueue($queueName);

            return $this->queues[$queueName] = $queue;
        }

        throw new \RuntimeException('Queue is not registered: ' . $queueName);
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
}
