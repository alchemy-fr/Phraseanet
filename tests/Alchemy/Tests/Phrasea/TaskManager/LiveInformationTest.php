<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\TaskManager\LiveInformation;
use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Alchemy\Phrasea\Model\Entities\Task;

class LiveInformationTest extends \PhraseanetTestCase
{
    public function testItReturnsWorkingManagerStatus()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue([
                    'manager' => [
                        'process-id' => 1234,
                    ]
                ]));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = [
            'configuration' => TaskManagerStatus::STATUS_STARTED,
            'actual'        => TaskManagerStatus::STATUS_STARTED,
            'process-id'    => 1234,
        ];
        $this->assertEquals($expected, $live->getManager());
    }

    public function testItReturnsNonWorkingManagerStatus()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue(null));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = [
            'configuration' => TaskManagerStatus::STATUS_STARTED,
            'actual'        => TaskManagerStatus::STATUS_STOPPED,
            'process-id'    => null,
        ];
        $this->assertEquals($expected, $live->getManager());
    }

    public function testItReturnsWorkingTaskStatus()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue([
                    'manager' => [
                        'process-id' => 1234,
                    ],
                    'jobs' => [
                        $task->getId() => [
                            'status'     => Task::STATUS_STARTED,
                            'process-id' => 1235,
                        ]
                    ],
                ]));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = [
            'configuration' => $task->getStatus(),
            'actual'        => Task::STATUS_STARTED,
            'process-id'    => 1235,
        ];
        $this->assertEquals($expected, $live->getTask($task));
    }

    public function testTaskManagerStatusIsPreponderantOverTaskStatusIfStopped()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
            ->method('notify')
            ->with(Notifier::MESSAGE_INFORMATIONS)
            ->will($this->returnValue([
                'manager' => [
                    'process-id' => 1234,
                ],
                'jobs' => [
                    $task->getId() => [
                        'status'     => Task::STATUS_STARTED,
                        'process-id' => 1235,
                    ]
                ],
            ]));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STOPPED), $notifier);
        $expected = [
            'configuration' => TaskManagerStatus::STATUS_STOPPED,
            'actual'        => Task::STATUS_STARTED,
            'process-id'    => 1235,
        ];
        $this->assertEquals($expected, $live->getTask($task));
    }

    public function testItReturnsNonWorkingTaskStatus()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue(null));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = [
            'configuration' => $task->getStatus(),
            'actual'        => Task::STATUS_STOPPED,
            'process-id'    => null,
        ];
        $this->assertEquals($expected, $live->getTask($task));
    }

    private function createStatusMock($status)
    {
        $managerStatus = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\TaskManagerStatus')
            ->disableOriginalConstructor()
            ->getMock();
        $managerStatus->expects($this->any())
                ->method('getStatus')
                ->will($this->returnValue($status));

        return $managerStatus;
    }

    private function createNotifierMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\TaskManager\Notifier')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
