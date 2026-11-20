<?php

/*
 * This file is part of alchemy/worker-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Worker;

use Alchemy\Queue\Message;
use Alchemy\Queue\MessageHandler;
use Alchemy\Queue\MessageHandlerResolver;
use Alchemy\Queue\MessageHandlingException;
use Alchemy\Queue\MessageQueueRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MessageDispatcher implements MessageHandler, LoggerAwareInterface
{

    /**
     * @var MessageHandlerResolver
     */
    private $handlerResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageQueueRegistry
     */
    private $queueRegistry;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var WorkerInvoker
     */
    private $workerInvoker;

    /**
     * @param WorkerInvoker $workerInvoker;
     * @param MessageQueueRegistry $queueRegistry
     * @param string $queueName
     */
    public function __construct(WorkerInvoker $workerInvoker, MessageQueueRegistry $queueRegistry, $queueName)
    {
        $this->queueRegistry = $queueRegistry;
        $this->queueName = $queueName;
        $this->workerInvoker = $workerInvoker;

        $this->handlerResolver = new MessageHandlerResolver($this);
        $this->logger = new NullLogger();
    }

    public function run()
    {
        $queue = $this->queueRegistry->getQueue($this->queueName);

        $this->logger->info(sprintf('Waiting for next message in queue \'%s\'', $this->queueName));

        $queue->handle($this->handlerResolver);
    }

    /**
     * @param Message $message
     * @return bool
     */
    public function accepts(Message $message)
    {
        $data = json_decode($message->getBody(), true);

        return json_last_error() == JSON_ERROR_NONE
            && isset($data['message_type']);
    }

    /**
     * @param Message $message
     * @throws MessageHandlingException when the message cannot be processed
     */
    public function handle(Message $message)
    {
        $data = json_decode($message->getBody(), true);

        $this->logger->info('Dispatching message to worker process: ' . $data['message_type']);
        $this->workerInvoker->invokeWorker($data['message_type'], json_encode($data['payload']));
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
