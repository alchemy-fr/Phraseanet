<?php

namespace Alchemy\Tests\Phrasea\Websocket\Subscriber;

use Alchemy\Phrasea\Websocket\Subscriber\TaskManagerBroadcasterSubscriber;
use Alchemy\Phrasea\Websocket\Topics\TopicsManager;
use Alchemy\TaskManager\Event\TaskManagerEvent;
use Alchemy\TaskManager\Event\TaskManagerEvents;
use Alchemy\TaskManager\Event\TaskManagerRequestEvent;

class TaskManagerBroadcasterSubscriberTest extends \PhraseanetTestCase
{
    public function testOnManagerStart()
    {
        $socket = $this->createZMQSocketMock();
        $socket->expects($this->once())
               ->method('send')
               ->will($this->jsonCapture($json));

        $subscriber = new TaskManagerBroadcasterSubscriber($socket);
        $subscriber->onManagerStart($this->createTaskManagerEvent());

        $this->assertValidJson($json, TopicsManager::TOPIC_TASK_MANAGER, TaskManagerEvents::MANAGER_START);
    }

    public function testOnManagerStop()
    {
        $socket = $this->createZMQSocketMock();
        $socket->expects($this->once())
               ->method('send')
               ->will($this->jsonCapture($json));

        $subscriber = new TaskManagerBroadcasterSubscriber($socket);
        $subscriber->onManagerStop($this->createTaskManagerEvent());

        $this->assertValidJson($json, TopicsManager::TOPIC_TASK_MANAGER, TaskManagerEvents::MANAGER_STOP);
    }

    public function testOnManagerRequest()
    {
        $socket = $this->createZMQSocketMock();
        $socket->expects($this->once())
               ->method('send')
               ->will($this->jsonCapture($json));

        $subscriber = new TaskManagerBroadcasterSubscriber($socket);
        $subscriber->onManagerRequest(new TaskManagerRequestEvent($this->createTaskManagerMock(), 'PING', 'PONG'));

        $data = $this->assertValidJson($json, TopicsManager::TOPIC_TASK_MANAGER, TaskManagerEvents::MANAGER_REQUEST);

        $this->assertEquals('PING', $data['request']);
        $this->assertEquals('PONG', $data['response']);
    }

    public function testOnManagerTick()
    {
        $socket = $this->createZMQSocketMock();
        $socket->expects($this->once())
               ->method('send')
               ->will($this->jsonCapture($json));

        $subscriber = new TaskManagerBroadcasterSubscriber($socket);
        $subscriber->onManagerTick($this->createTaskManagerEvent());

        $data = $this->assertValidJson($json, TopicsManager::TOPIC_TASK_MANAGER, TaskManagerEvents::MANAGER_TICK);

        $this->assertArrayHasKey('message', $data);
        $this->assertInternalType('array', $data['message']);
    }

    private function assertValidJson($json, $topic, $event)
    {
        $data = json_decode($json, true);

        $this->assertTrue(json_last_error() === JSON_ERROR_NONE);
        $this->assertArrayHasKey('event', $data);
        $this->assertArrayHasKey('topic', $data);

        $this->assertEquals($event, $data['event']);
        $this->assertEquals($topic, $data['topic']);

        return $data;
    }

    private function jsonCapture(&$json)
    {
        return $this->returnCallback(function ($arg) use (&$json) { $json = $arg; return 'lala'; });
    }

    private function createZMQSocketMock()
    {
        $socket = $this->getMockBuilder('Alchemy\TaskManager\ZMQSocket')
            ->setMethods(['send', 'bind'])
            ->disableOriginalConstructor()
            ->getMock();
        $socket->expects($this->once())
            ->method('bind');

        return $socket;
    }

    private function createTaskManagerMock()
    {
        $manager = $this->getMockBuilder('Alchemy\TaskManager\TaskManager')
            ->disableOriginalConstructor()
            ->getMock();

        $processManager = $this->getMockBuilder('Neutron\ProcessManager\ProcessManager')
            ->disableOriginalConstructor()
            ->getMock();

        $processManager->expects($this->any())
            ->method('getManagedProcesses')
            ->will($this->returnValue([]));

        $manager->expects($this->any())
            ->method('getProcessManager')
            ->will($this->returnValue($processManager));

        return $manager;
    }

    private function createTaskManagerEvent()
    {
        return new TaskManagerEvent($this->createTaskManagerMock());
    }
}
