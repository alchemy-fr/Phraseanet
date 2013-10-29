<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskManipulatorTest extends \PhraseanetPHPUnitAbstract
{
    public function testCreate()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_CREATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier);
        $this->assertCount(0, $this->findAllTasks());
        $task = $manipulator->create('prout', 'bla bla', 'super settings', 0);
        $this->assertEquals('prout', $task->getName());
        $this->assertEquals('bla bla', $task->getJobId());
        $this->assertEquals('super settings', $task->getSettings());
        $this->assertEquals(0, $task->getPeriod());
        $this->assertSame(array($task), $this->findAllTasks());

        return $task;
    }

    public function testUpdate()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_UPDATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier);
        $task = $this->loadTask();
        $task->setName('new name');
        $this->assertSame($task, $manipulator->update($task));
        self::$DI['app']['EM']->clear();
        $this->assertEquals(array($task), $this->findAllTasks());
    }

    public function testDelete()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_DELETE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier);
        $task = $this->loadTask();
        $manipulator->delete($task);
        $this->assertEquals(array(), $this->findAllTasks());
    }

    public function testStart()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_UPDATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier);
        $task = $this->loadTask();
        $task->setStatus(Task::STATUS_STOPPED);
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();
        $manipulator->start($task);
        $this->assertEquals(Task::STATUS_STARTED, $task->getStatus());
    }

    public function testStop()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_UPDATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier);
        $task = $this->loadTask();
        $task->setStatus(Task::STATUS_STARTED);
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();
        $manipulator->stop($task);
        $this->assertEquals(Task::STATUS_STOPPED, $task->getStatus());
    }

    public function testResetCrashes()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_UPDATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier);
        $task = $this->loadTask();
        $task->setCrashed(42);
        $manipulator->resetCrashes($task);
        $this->assertEquals(0, $task->getCrashed());
    }

    public function testGetRepository()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $this->createNotifierMock());
        $this->assertSame(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task'), $manipulator->getRepository());
    }

    public function testCreateEmptyCollection()
    {
        $collection = $this->getMockBuilder('collection')
                ->disableOriginalConstructor()
                ->getMock();
        $collection->expects($this->once())
                ->method('get_base_id')
                ->will($this->returnValue(42));

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $this->createNotifierMock());
        $task = $manipulator->createEmptyCollectionJob($collection);

        $tasks = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task')->findAll();
        $this->assertSame('EmptyCollection', $task->getJobId());
        $this->assertSame(array($task), $tasks);
        $settings = simplexml_load_string($task->getSettings());
        $this->assertEquals(42, (int) $settings->bas_id);
    }
    private function findAllTasks()
    {
        return self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task')->findAll();
    }

    private function loadTask()
    {
        $task = new Task();
        $task
            ->setName('test')
            ->setJobId('SuperSpace');

        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        return $task;
    }

    private function createNotifierMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\TaskManager\Notifier')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
