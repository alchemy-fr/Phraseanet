<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event\TaskManagerSubscriber;

use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\TaskManager\Event\TaskManagerEvent;
use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\TaskManager\ZMQSocket;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes a lock file to prevent running the task manager multiple times concurrently.
 */
class StatusOnTickPublisherSubscriber implements EventSubscriberInterface
{
    /** @var ZMQSocket */
    private $socket;
    /** @var StateFormater */
    private $formater;
    private $latest;

    public function __construct(ZMQSocket $socket, StateFormater $formater)
    {
        $this->socket = $socket;
        $this->formater = $formater;
    }

    public static function getSubscribedEvents()
    {
        return array(
            TaskManagerEvents::MANAGER_START => 'onManagerStart',
            TaskManagerEvents::MANAGER_TICK  => 'onManagerTick',
            TaskManagerEvents::MANAGER_STOP  => 'onManagerStop',
        );
    }

    public function onManagerStart(TaskManagerEvent $event)
    {
        $this->socket->bind();
    }

    public function onManagerStop(TaskManagerEvent $event)
    {
        $this->socket->unbind();
        $this->latest = null;
    }

    public function onManagerTick(TaskManagerEvent $event)
    {
        $data = $this->formater->toArray(
            $event->getManager()->getProcessManager()->getManagedProcesses()
        );
        if ($data !== $this->latest) {
            $this->socket->send(json_encode($this->latest = $data));
        }
    }

    public static function create(array $options = array())
    {
        $options = array_replace(array(
            'protocol'  => 'tcp',
            'host'      => '127.0.0.1',
            'port'      => 6661,
        ), $options);
        $socket = new ZMQSocket(
            new \ZMQContext(), \ZMQ::SOCKET_PUB,
            $options['protocol'],
            $options['host'],
            $options['port']
        );

        return new StatusOnTickPublisherSubscriber($socket, new StateFormater());
    }
}
