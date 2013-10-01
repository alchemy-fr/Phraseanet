<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\TaskManager\LiveInformation;
use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Entities\Task;

class LiveInformationTest extends \PhraseanetPHPUnitAbstract
{
    public function testItReturnsWorkingManagerStatus()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue(array(
                    'manager' => array(
                        'process-id' => 1234,
                    )
                )));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = array(
            'configuration' => TaskManagerStatus::STATUS_STARTED,
            'actual'        => TaskManagerStatus::STATUS_STARTED,
            'process-id'    => 1234,
        );
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
        $expected = array(
            'configuration' => TaskManagerStatus::STATUS_STARTED,
            'actual'        => TaskManagerStatus::STATUS_STOPPED,
            'process-id'    => null,
        );
        $this->assertEquals($expected, $live->getManager());
    }


    public function testItReturnsWorkingTaskStatus()
    {
        $task = new Task();
        $task->setName('Task')->setJobId('Null');

        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue(array(
                    'manager' => array(
                        'process-id' => 1234,
                    ),
                    'jobs' => array(
                        $task->getId() => array(
                            'status'     => Task::STATUS_STARTED,
                            'process-id' => 1235,
                        )
                    ),
                )));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = array(
            'configuration' => Task::STATUS_STARTED,
            'actual'        => Task::STATUS_STARTED,
            'process-id'    => 1235,
        );
        $this->assertEquals($expected, $live->getTask($task));
    }

    public function testItReturnsNonWorkingTaskStatus()
    {
        $task = new Task();
        $task->setName('Task')->setJobId('Null');

        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_INFORMATIONS)
                ->will($this->returnValue(null));

        $live = new LiveInformation($this->createStatusMock(TaskManagerStatus::STATUS_STARTED), $notifier);
        $expected = array(
            'configuration' => Task::STATUS_STARTED,
            'actual'        => Task::STATUS_STOPPED,
            'process-id'    => null,
        );
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
