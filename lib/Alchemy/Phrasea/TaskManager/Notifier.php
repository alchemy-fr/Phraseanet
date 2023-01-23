<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\TaskManager\TaskManager;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

class Notifier implements NotifierInterface
{
    /** @var \ZMQSocket */
    private $socket;

    /** @var LoggerInterface */
    private $logger;

    /** @var integer  */
    private $timeout = 10;

    public function __construct(\ZMQSocket $socket, LoggerInterface $logger)
    {
        $this->socket = $socket;
        $this->logger = $logger;
    }

    public function setTimeout($timeout)
    {
        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be a positive value');
        }
        $this->timeout = (float) $timeout;
    }

    /**
     * Notifies the task manager given a message constant, see MESSAGE_* constants.
     *
     * @param string $message
     *
     * @return mixed|null The return value of the task manager.
     *
     * @throws RuntimeException in case notification did not occur within the timeout.
     */
    public function notify($message)
    {
        try {
            $command = $this->createCommand($message);
            $this->socket->send($command);

            $result = false;
            $limit = microtime(true) + $this->timeout;

            while (microtime(true) < $limit && false === $result = $this->socket->recv(\ZMQ::MODE_NOBLOCK)) {
                usleep(1000);
            }

            if (false === $result) {
                $this->logger->error(sprintf('Unable to notify the task manager with message "%s" within timeout of %d seconds', $message, $this->timeout));
                throw new RuntimeException('Unable to retrieve information.');
            }

            $data = @json_decode($result, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RuntimeException('Invalid task manager response : invalid JSON.');
            }
            if (!isset($data['reply']) || !isset($data['request']) || $command !== $data['request']) {
                throw new RuntimeException('Invalid task manager response : missing fields.');
            }

            return $data['reply'];
        } catch (\ZMQSocketException $e) {
            $this->logger->error(sprintf('Unable to notify the task manager with message "%s" within timeout of %d seconds', $message, $this->timeout), ['exception' => $e]);
            throw new RuntimeException('Unable to retrieve information.', $e->getCode(), $e);
        }
    }

    private function createCommand($message)
    {
        switch ($message) {
            case static::MESSAGE_CREATE:
            case static::MESSAGE_UPDATE:
            case static::MESSAGE_DELETE:
                return TaskManager::MESSAGE_PROCESS_UPDATE;
            case static::MESSAGE_INFORMATION:
                return TaskManager::MESSAGE_STATE;
            default:
                throw new InvalidArgumentException(sprintf('Unable to understand %s message notification', $message));
        }
    }

    /**
     * Creates a Notifier.
     *
     * @param array $options
     *
     * @return Notifier
     */
    public static function create(LoggerInterface $logger, array $options = [])
    {
        $context = new \ZMQContext();
        $socket = $context->getSocket(\ZMQ::SOCKET_REQ);
        $socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, $options['linger']);
        $socket->connect(sprintf(
            '%s://%s:%s', $options['protocol'], $options['host'], $options['port']
        ));

        return new static($socket, $logger);
    }
}
