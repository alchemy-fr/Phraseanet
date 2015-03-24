<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\TaskManager\NotifierInterface;
use Alchemy\Phrasea\Model\Entities\Task;
use Doctrine\Common\Persistence\ObjectManager;

class TaskManipulatorTest extends \PhraseanetTestCase
{
    /** @var NotifierInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $notifier;

    /** @var TaskManipulator */
    private $sut;

    public function setUp()
    {
        parent::setUp();
        $this->notifier = $this->createNotifierMock();

        $this->sut = new TaskManipulator(self::$DI['app']['orm.em'], self::$DI['app']['translator'], $this->notifier);
    }

    public function testCreate()
    {
        $this->notifier
            ->expects($this->once())
            ->method('notify')
            ->with(NotifierInterface::MESSAGE_CREATE);

        $this->assertCount(2, $this->findAllTasks());

        $task = $this->sut->create('prout', 'bla bla', 'super settings', 0);

        $this->assertEquals('prout', $task->getName());
        $this->assertEquals('bla bla', $task->getJobId());
        $this->assertEquals('super settings', $task->getSettings());
        $this->assertEquals(0, $task->getPeriod());

        $allTasks = $this->findAllTasks();
        $this->assertCount(3, $allTasks);
        $this->assertContains($task, $allTasks);
    }

    public function testUpdate()
    {
        $this->notifier
            ->expects($this->once())
            ->method('notify')
            ->with(NotifierInterface::MESSAGE_UPDATE);

        $task = $this->loadTask();
        $task->setName('new name');
        $this->assertSame($task, $this->sut->update($task));
        self::$DI['app']['orm.em']->clear();
        $updated = self::$DI['app']['orm.em']->find('Phraseanet:Task', 1);
        $this->assertEquals($task, $updated);
    }

    public function testDelete()
    {
        $this->notifier
            ->expects($this->once())
            ->method('notify')
            ->with(NotifierInterface::MESSAGE_DELETE);

        $task = $this->loadTask();
        $this->sut->delete($task);
        $this->assertNotContains($task, $this->findAllTasks());
    }

    public function testStart()
    {
        $this->notifier
            ->expects($this->once())
            ->method('notify')
            ->with(NotifierInterface::MESSAGE_UPDATE);

        $task = $this->loadTask();
        $task->setStatus(Task::STATUS_STOPPED);
        self::$DI['app']['orm.em']->persist($task);
        self::$DI['app']['orm.em']->flush();

        $this->sut->start($task);
        $this->assertEquals(Task::STATUS_STARTED, $task->getStatus());
    }

    public function testStop()
    {
        $this->notifier
            ->expects($this->once())
            ->method('notify')
            ->with(NotifierInterface::MESSAGE_UPDATE);

        $task = $this->loadTask();
        $task->setStatus(Task::STATUS_STARTED);
        self::$DI['app']['orm.em']->persist($task);
        self::$DI['app']['orm.em']->flush();

        $this->sut->stop($task);

        $this->assertEquals(Task::STATUS_STOPPED, $task->getStatus());
    }

    public function testResetCrashes()
    {
        $this->notifier
            ->expects($this->once())
            ->method('notify')
            ->with(NotifierInterface::MESSAGE_UPDATE);

        $task = $this->loadTask();
        $task->setCrashed(42);

        $this->sut->resetCrashes($task);

        $this->assertEquals(0, $task->getCrashed());
    }

    public function testCreateEmptyCollection()
    {
        $collection = $this->getMockBuilder('collection')
                ->disableOriginalConstructor()
                ->getMock();
        $collection->expects($this->once())
                ->method('get_base_id')
                ->will($this->returnValue(42));

        $task = $this->sut->createEmptyCollectionJob($collection);

        $tasks = $this->findAllTasks();
        $this->assertSame('EmptyCollection', $task->getJobId());
        $this->assertContains($task, $tasks);
        $settings = simplexml_load_string($task->getSettings());
        $this->assertEquals(42, (int) $settings->bas_id);
    }

    public function testItsNotifierCanBeChanged()
    {
        $notifier = $this->createNotifierMock();

        $this->assertSame($this->sut, $this->sut->setNotifier($notifier));
        $this->assertAttributeSame($notifier, 'notifier', $this->sut);
    }

    /**
     * @return Task[]
     */
    private function findAllTasks()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = self::$DI['app']['orm.em'];
        return $objectManager->getRepository(Task::class)->findAll();
    }

    /**
     * @return Task
     */
    private function loadTask()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = self::$DI['app']['orm.em'];
        return $objectManager->find(Task::class, 1);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createNotifierMock()
    {
        return $this->getMockBuilder(NotifierInterface::class)->getMock();
    }
}
