<?php

namespace Alchemy\Test\TaskManager\Event\TaskManagerSubscriber;

use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\Test\TaskManager\TestCase;

abstract class SubscriberTestCase extends TestCase
{
    public function testThatEventsAreRecognized()
    {
        foreach (array_keys($this->getSubscriber()->getSubscribedEvents()) as $name) {
            $this->assertContains($name, array(
                TaskManagerEvents::MANAGER_START,
                TaskManagerEvents::MANAGER_STOP,
                TaskManagerEvents::MANAGER_REQUEST,
                TaskManagerEvents::MANAGER_TICK,
            ));
        }
    }

    abstract protected function getSubscriber();
}
