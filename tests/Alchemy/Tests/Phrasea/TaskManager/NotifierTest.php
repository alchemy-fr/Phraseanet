<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Phrasea\TaskManager\NotifierInterface;
use Alchemy\TaskManager\TaskManager;
use Psr\Log\LoggerInterface;

class NotifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var \ZMQSocket|\PHPUnit_Framework_MockObject_MockObject */
    private $socket;
    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var Notifier */
    private $sut;

    protected function setUp()
    {
        $this->socket = $this->createSocketMock();
        $this->logger = $this->getMock(LoggerInterface::class);

        $this->sut = new Notifier($this->socket, $this->logger);
    }

    public function testItImplementsNotifierInterface()
    {
        $this->assertInstanceOf(NotifierInterface::class, $this->sut);
    }

    /**
     * @dataProvider provideMessagesData
     */
    public function testNotify($message, $expectedCommand, $result, $expectedReturnValue)
    {
        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with($expectedCommand);

        $this->socket
            ->expects($this->once())
            ->method('recv')
            ->will($this->returnValue($result));

        $this->assertEquals($expectedReturnValue, $this->sut->notify($message));
    }

    public function provideMessagesData()
    {
        $managerData = ['manager' => ['process-id' => 1234], 'jobs' => ['24' => ['process-id' => 4567, 'status' => 'started']]];

        return [
            [Notifier::MESSAGE_CREATE, TaskManager::MESSAGE_PROCESS_UPDATE, json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE, 'reply' => TaskManager::RESPONSE_OK]), TaskManager::RESPONSE_OK],
            [Notifier::MESSAGE_DELETE, TaskManager::MESSAGE_PROCESS_UPDATE, json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE, 'reply' => TaskManager::RESPONSE_OK]), TaskManager::RESPONSE_OK],
            [Notifier::MESSAGE_UPDATE, TaskManager::MESSAGE_PROCESS_UPDATE, json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE, 'reply' => TaskManager::RESPONSE_OK]), TaskManager::RESPONSE_OK],
            [Notifier::MESSAGE_INFORMATION, TaskManager::MESSAGE_STATE, json_encode(['request' => TaskManager::MESSAGE_STATE, 'reply' => $managerData]), $managerData],
        ];
    }

    public function testNoresultsThrowsException()
    {
        $this->socket
            ->expects($this->any())
            ->method('recv')
            ->will($this->returnValue(false));

        $this->setExpectedException(RuntimeException::class, 'Unable to retrieve information.');
        $this->sut->notify(Notifier::MESSAGE_CREATE);
    }

    public function testWrongJsonReturnNull()
    {
        $this->socket
            ->expects($this->once())
            ->method('recv')
            ->will($this->returnValue('wrong json'));

        $this->setExpectedException(RuntimeException::class, 'Invalid task manager response : invalid JSON.');
        $this->sut->notify(Notifier::MESSAGE_CREATE);
    }

    public function testWrongReplyReturnNull()
    {
        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with(TaskManager::MESSAGE_PROCESS_UPDATE);

        $this->socket
            ->expects($this->once())
            ->method('recv')
            ->will($this->returnValue(json_encode(['request' => 'popo', 'reply' => []])));

        $this->setExpectedException(RuntimeException::class, 'Invalid task manager response : missing fields.');
        $this->sut->notify(Notifier::MESSAGE_CREATE);
    }

    public function testMissingRequestReturnNull()
    {
        $this->socket
            ->expects($this->once())
            ->method('send')
            ->with(TaskManager::MESSAGE_PROCESS_UPDATE);

        $this->socket
            ->expects($this->once())
            ->method('recv')
            ->will($this->returnValue(json_encode(['request' => TaskManager::MESSAGE_PROCESS_UPDATE])));

        $this->setExpectedException(RuntimeException::class, 'Invalid task manager response : missing fields.');
        $this->sut->notify(Notifier::MESSAGE_CREATE);
    }

    private function createSocketMock()
    {
        return $this->getMockBuilder('ZMQSocket')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
