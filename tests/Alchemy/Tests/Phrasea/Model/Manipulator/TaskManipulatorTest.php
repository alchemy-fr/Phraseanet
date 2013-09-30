<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Entities\Task;

class TaskManipulatorTest extends \PhraseanetPHPUnitAbstract
{
    private function findAllTasks()
    {
        return self::$DI['app']['EM']->getRepository('Entities\Task')->findAll();
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

    public function testCreate()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
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
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $task = $this->loadTask();
        $task->setName('new name');
        $this->assertSame($task, $manipulator->update($task));
        self::$DI['app']['EM']->clear();
        $this->assertEquals(array($task), $this->findAllTasks());
    }

    public function testDelete()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $task = $this->loadTask();
        $manipulator->delete($task);
        $this->assertEquals(array(), $this->findAllTasks());
    }

    public function testStart()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $task = $this->loadTask();
        $task->setStatus(Task::STATUS_STOPPED);
        $manipulator->update($task);
        $manipulator->start($task);
        $this->assertEquals(Task::STATUS_STARTED, $task->getStatus());
    }

    public function testStop()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $task = $this->loadTask();
        $task->setStatus(Task::STATUS_STARTED);
        $manipulator->update($task);
        $manipulator->stop($task);
        $this->assertEquals(Task::STATUS_STOPPED, $task->getStatus());
    }

    public function testResetCrashes()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $task = $this->loadTask();
        $task->setCrashed(42);
        $manipulator->resetCrashes($task);
        $this->assertEquals(0, $task->getCrashed());
    }

    public function testGetRepository()
    {
        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $this->assertSame(self::$DI['app']['EM']->getRepository('Entities\Task'), $manipulator->getRepository());
    }

    public function testCreateEmptyCollection()
    {
        $collection = $this->getMockBuilder('collection')
                ->disableOriginalConstructor()
                ->getMock();
        $collection->expects($this->once())
                ->method('get_base_id')
                ->will($this->returnValue(42));

        $manipulator = new TaskManipulator(self::$DI['app']['EM']);
        $task = $manipulator->createEmptyCollectionJob($collection);

        $tasks = self::$DI['app']['EM']->getRepository('Entities\Task')->findAll();
        $this->assertSame('EmptyCollection', $task->getJobId());
        $this->assertSame(array($task), $tasks);
        $settings = simplexml_load_string($task->getSettings());
        $this->assertEquals(42, (int) $settings->bas_id);
    }
}
