<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Event;

use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\TaskManager\Event\JobEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PhraseanetIndexerStopperSubscriber implements EventSubscriberInterface
{
    private $port;

    public function __construct($port)
    {
        $this->port = $port;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            TaskManagerEvents::STOP_REQUEST => ['onStopRequest'],
        ];
    }

    public function onStopRequest(JobEvent $event)
    {
        if (false !== $socket = socket_create(AF_INET, SOCK_STREAM, 0)) {
            if (socket_connect($socket, '127.0.0.1', $this->port) === true) {
                socket_write($socket, 'Q', 1);
                socket_write($socket, "\r\n", strlen("\r\n"));
            }
            socket_close($socket);
        }
        unset($socket);
    }
}
