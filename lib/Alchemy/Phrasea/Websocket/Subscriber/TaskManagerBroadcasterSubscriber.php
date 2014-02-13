<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Websocket\Subscriber;

use Alchemy\Phrasea\Websocket\Topics\TopicsManager;
use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\TaskManager\Event\TaskManagerEvent;
use Alchemy\TaskManager\Event\TaskManagerRequestEvent;
use Alchemy\TaskManager\Event\TaskManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Alchemy\TaskManager\ZMQSocket;

class TaskManagerBroadcasterSubscriber implements EventSubscriberInterface
{
    private $broadcaster;
    private $formater;

    public function __construct(ZMQSocket $broadcaster)
    {
        $this->formater = new StateFormater();

        $this->broadcaster = $broadcaster;
        $this->broadcaster->bind();

        usleep(300000);
    }

    public function onManagerStart(TaskManagerEvent $event)
    {
        $this->broadcaster->send(json_encode([
            'topic' => TopicsManager::TOPIC_TASK_MANAGER,
            'event' => TaskManagerEvents::MANAGER_START,
        ]));
    }

    public function onManagerStop(TaskManagerEvent $event)
    {
        $this->broadcaster->send(json_encode([
            'topic' => TopicsManager::TOPIC_TASK_MANAGER,
            'event' => TaskManagerEvents::MANAGER_STOP,
        ]));
    }

    public function onManagerRequest(TaskManagerRequestEvent $event)
    {
        $this->broadcaster->send(json_encode([
            'topic' => TopicsManager::TOPIC_TASK_MANAGER,
            'event' => TaskManagerEvents::MANAGER_REQUEST,
            'request' => $event->getRequest(),
            'response' => $event->getResponse(),
        ]));
    }

    public function onManagerTick(TaskManagerEvent $event)
    {
        $this->broadcaster->send(json_encode([
            'topic' => TopicsManager::TOPIC_TASK_MANAGER,
            'event' => TaskManagerEvents::MANAGER_TICK,
            'message' => $this->formater->toArray(
                $event->getManager()->getProcessManager()->getManagedProcesses()
            ),
        ]));
    }

    public static function getSubscribedEvents()
    {
        return [
            TaskManagerEvents::MANAGER_START   => 'onManagerStart',
            TaskManagerEvents::MANAGER_STOP    => 'onManagerStop',
            TaskManagerEvents::MANAGER_REQUEST => 'onManagerRequest',
            TaskManagerEvents::MANAGER_TICK    => 'onManagerTick',
        ];
    }

    public static function create(array $options)
    {
        return new static(ZMQSocket::create(new \ZMQContext(), \ZMQ::SOCKET_PUB, $options['protocol'], $options['host'], $options['port']));
    }
}
