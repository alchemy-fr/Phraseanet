<?php

namespace Alchemy\Test\TaskManager\Event\TaskManagerSubscriber;

use Alchemy\TaskManager\Event\StateFormater;
use Alchemy\TaskManager\Event\TaskManagerSubscriber\StatusRequestSubscriber;
use Alchemy\TaskManager\TaskManager;
use Alchemy\TaskManager\Event\TaskManagerRequestEvent;

class StatusRequestSubscriberTest extends SubscriberTestCase
{
    public function testSetStatusOnRequest()
    {
        $processManager = $this->getMockBuilder('Neutron\ProcessManager\ProcessManager')
                ->disableOriginalConstructor()
                ->getMock();
        $processManager->expects($this->once())
                ->method('getManagedProcesses')
                ->willReturn(array());

        $manager = $this->getMockBuilder('Alchemy\TaskManager\TaskManager')
                ->disableOriginalConstructor()
                ->getMock();
        $manager->expects($this->once())
                ->method('getProcessManager')
                ->willReturn($processManager);

        $subscriber = $this->getSubscriber();
        $event = new TaskManagerRequestEvent($manager, TaskManager::MESSAGE_STATE, TaskManager::RESPONSE_UNHANDLED_MESSAGE);
        $subscriber->onManagerRequest($event);
        $this->assertIsArray( $event->getResponse());
    }

    protected function getSubscriber()
    {
        return new StatusRequestSubscriber(new StateFormater());
    }
}
