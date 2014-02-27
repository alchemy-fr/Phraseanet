<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\TaskManager\Notifier;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_CREATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier, self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
        $this->assertCount(2, $this->findAllTasks());
        $task = $manipulator->create('prout', 'bla bla', 'super settings', 0);
        $this->assertEquals('prout', $task->getName());
        $this->assertEquals('bla bla', $task->getJobId());
        $this->assertEquals('super settings', $task->getSettings());
        $this->assertEquals(0, $task->getPeriod());
        $allTasks = $this->findAllTasks();
        $this->assertCount(3, $allTasks);
        $this->assertContains($task, $allTasks);

        return $task;
    }

    public function testUpdate()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_UPDATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier, self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
        $task = $this->loadTask();
        $task->setName('new name');
        $this->assertSame($task, $manipulator->update($task));
        self::$DI['app']['EM']->clear();
        $updated = self::$DI['app']['EM']->find('Phraseanet:Task', 1);
        $this->assertEquals($task, $updated);
    }

    public function testDelete()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_DELETE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier, self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
        $task = $this->loadTask();
        $manipulator->delete($task);
        $this->assertNotContains($task, $this->findAllTasks());
    }

    public function testStart()
    {
        $notifier = $this->createNotifierMock();
        $notifier->expects($this->once())
                ->method('notify')
                ->with(Notifier::MESSAGE_UPDATE);

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier, self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
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

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier, self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
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

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $notifier, self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
        $task = $this->loadTask();
        $task->setCrashed(42);
        $manipulator->resetCrashes($task);
        $this->assertEquals(0, $task->getCrashed());
    }

    public function testGetRepository()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $this->createNotifierMock(), self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
        $this->assertSame(self::$DI['app']['EM']->getRepository('Phraseanet:Task'), $manipulator->getRepository());
    }

    public function testCreateEmptyCollection()
    {
        $collection = $this->getMockBuilder('collection')
                ->disableOriginalConstructor()
                ->getMock();
        $collection->expects($this->once())
                ->method('get_base_id')
                ->will($this->returnValue(42));

        $manipulator = new TaskManipulator(self::$DI['app']['EM'], $this->createNotifierMock(), self::$DI['app']['translator'], self::$DI['app']['repo.tasks']);
        $task = $manipulator->createEmptyCollectionJob($collection);

        $tasks = self::$DI['app']['EM']->getRepository('Phraseanet:Task')->findAll();
        $this->assertSame('EmptyCollection', $task->getJobId());
        $this->assertContains($task, $tasks);
        $settings = simplexml_load_string($task->getSettings());
        $this->assertEquals(42, (int) $settings->bas_id);
    }
    private function findAllTasks()
    {
        return self::$DI['app']['EM']->getRepository('Phraseanet:Task')->findAll();
    }

    private function loadTask()
    {
        return self::$DI['app']['EM']->find('Phraseanet:Task', 1);
    }

    private function createNotifierMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\TaskManager\Notifier')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
