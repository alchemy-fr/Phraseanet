<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\TaskManager\TaskManager;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Notifier
{
    /** Alerts the task manager a new Task has been created */
    const MESSAGE_CREATE = 'create';
    /** Alerts the task manager a Task has been updated */
    const MESSAGE_UPDATE = 'update';
    /** Alerts the task manager a Task has been deleted */
    const MESSAGE_DELETE = 'delete';
    /** Alerts the task manager to send its informations data */
    const MESSAGE_INFORMATIONS = 'informations';

    /** @var \ZMQSocket */
    private $socket;

    public function __construct(\ZMQSocket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * Notifies the task manager given a message constant, see MESSAGE_* constants.
     *
     * @param string $message
     *
     * @return mixed|null The return value of the task manager.
     */
    public function notify($message)
    {
        try {
            $command = $this->createCommand($message);
            $this->socket->send($command);

            $limit = microtime(true) + 0.5;
            while (microtime(true) < $limit && false === $result = $this->socket->recv(\ZMQ::MODE_NOBLOCK)) {
                usleep(1000);
            }
            if (false === $result) {
                return null;
            }

            $data = @json_decode($result, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                return null;
            }
            if (!isset($data['reply']) || !isset($data['request']) || $command !== $data['request']) {
                return null;
            }

            return $data['reply'];
        } catch (\ZMQSocketException $e) {

        }

        return null;
    }

    private function createCommand($message)
    {
        switch ($message) {
            case static::MESSAGE_CREATE:
            case static::MESSAGE_UPDATE:
            case static::MESSAGE_DELETE:
                return TaskManager::MESSAGE_PROCESS_UPDATE;
            case static::MESSAGE_INFORMATIONS:
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
    public static function create(array $options = [])
    {
        $context = new \ZMQContext();
        $socket = $context->getSocket(\ZMQ::SOCKET_REQ);
        $socket->setSockOpt(\ZMQ::SOCKOPT_LINGER, $options['linger']);
        $socket->connect(sprintf(
            '%s://%s:%s', $options['protocol'], $options['host'], $options['port']
        ));

        return new static($socket);
    }
}
