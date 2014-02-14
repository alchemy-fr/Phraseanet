<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\TaskManager\TaskManager;
use Alchemy\Phrasea\TaskManager\Notifier;

class NotifierTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideMessagesData
     */
    public function testNotify($message, $expectedCommand, $result, $expectedReturnValue)
    {
        $socket = $this->createSocketMock();
        $socket->expects($this->once())
                ->method('send')
                ->with($expectedCommand);

        $socket->expects($this->once())
                ->method('recv')
                ->will($this->returnValue($result));

        $notifier = new Notifier($socket);
        $this->assertEquals($expectedReturnValue, $notifier->notify($message));
    }

    public function provideMessagesData()
    {
        $managerData = ['manager' => ['process-id' => 1234], 'jobs' => ['24' => ['process-id' => 4567, 'status' => 'started']]];

        return [
            [Notifier::MESSAGE_CREATE, TaskManager::MESSAGE_PROCESS_UPDATE, json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE, 'reply' => TaskManager::RESPONSE_OK]), TaskManager::RESPONSE_OK],
            [Notifier::MESSAGE_DELETE, TaskManager::MESSAGE_PROCESS_UPDATE, json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE, 'reply' => TaskManager::RESPONSE_OK]), TaskManager::RESPONSE_OK],
            [Notifier::MESSAGE_UPDATE, TaskManager::MESSAGE_PROCESS_UPDATE, json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE, 'reply' => TaskManager::RESPONSE_OK]), TaskManager::RESPONSE_OK],
            [Notifier::MESSAGE_INFORMATIONS, TaskManager::MESSAGE_STATE, json_encode(['request' => TaskManager::MESSAGE_STATE, 'reply' => $managerData]), $managerData],
        ];
    }

    public function testNoresultsReturnNull()
    {
        $socket = $this->createSocketMock();

        $socket->expects($this->any())
                ->method('recv')
                ->will($this->returnValue(false));

        $notifier = new Notifier($socket);
        $this->assertNull($notifier->notify(Notifier::MESSAGE_CREATE));
    }

    public function testWrongJsonReturnNull()
    {
        $socket = $this->createSocketMock();

        $socket->expects($this->once())
                ->method('recv')
                ->will($this->returnValue('wrong json'));

        $notifier = new Notifier($socket);
        $this->assertNull($notifier->notify(Notifier::MESSAGE_CREATE));
    }

    public function testWrongReplyReturnNull()
    {
        $socket = $this->createSocketMock();
        $socket->expects($this->once())
                ->method('send')
                ->with(TaskManager::MESSAGE_PROCESS_UPDATE);

        $socket->expects($this->once())
                ->method('recv')
                ->will($this->returnValue(json_encode(['request' => 'popo', 'reply' => []])));

        $notifier = new Notifier($socket);
        $this->assertNull($notifier->notify(Notifier::MESSAGE_CREATE));
    }

    public function testMissingRequestReturnNull()
    {
        $socket = $this->createSocketMock();
        $socket->expects($this->once())
                ->method('send')
                ->with(TaskManager::MESSAGE_PROCESS_UPDATE);

        $socket->expects($this->once())
                ->method('recv')
                ->will($this->returnValue(json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE])));

        $notifier = new Notifier($socket);
        $this->assertNull($notifier->notify(Notifier::MESSAGE_CREATE));
    }

    private function createSocketMock()
    {
        return $this->getMockBuilder('ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
