<?php

namespace Alchemy\Test\TaskManager\Event\JobSubscriber;

use Alchemy\TaskManager\Event\JobEvents;
use Alchemy\Test\TaskManager\TestCase;

abstract class SubscriberTestCase extends TestCase
{
    public function testThatEventsAreRecognized()
    {
        foreach (array_keys($this->getSubscriber()->getSubscribedEvents()) as $name) {
            $this->assertContains($name, array(
                JobEvents::START,
                JobEvents::TICK,
                JobEvents::STOP,
                JobEvents::EXCEPTION,
            ));
        }
    }

    abstract protected function getSubscriber();
}
