<?php

namespace Alchemy\Queue;

use Alchemy\Queue\Message;
use Alchemy\Queue\MessageHandler;
use Alchemy\Queue\MessageHandlingException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NullMessageHandler implements MessageHandler, LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
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
     * @param Message $message
     * @return bool
     */
    public function accepts(Message $message)
    {
        return true;
    }

    /**
     * @param Message $message
     * @throws MessageHandlingException when the message cannot be processed
     */
    public function handle(Message $message)
    {
        return;
    }
}
