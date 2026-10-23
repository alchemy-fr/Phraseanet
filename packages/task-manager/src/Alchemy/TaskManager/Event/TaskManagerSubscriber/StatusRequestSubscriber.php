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

use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\TaskManager\Event\TaskManagerRequestEvent;
use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\TaskManager\TaskManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes a lock file to prevent running the task manager multiple times concurrently.
 */
class StatusRequestSubscriber implements EventSubscriberInterface
{
    private $formater;

    public function __construct(StateFormater $formater)
    {
        $this->formater = $formater;
    }

    public static function getSubscribedEvents()
    {
        return array(
            TaskManagerEvents::MANAGER_REQUEST => 'onManagerRequest',
        );
    }

    public function onManagerRequest(TaskManagerRequestEvent $event)
    {
        if (TaskManager::MESSAGE_STATE !== $event->getRequest()) {
            return;
        }

        $event->setResponse(
            $this->formater->toArray(
                $event->getManager()->getProcessManager()->getManagedProcesses()
            )
        );
    }
}
