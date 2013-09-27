<?php

namespace Doctrine\Tests\Repositories;

use Entities\Task;

class TaskRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFindActiveTask()
    {
        $task1 = new Task();
        $task1
            ->setName('task 1')
            ->setStatus(Task::STATUS_STOPPED)
            ->setClassname('Alchemy\Phrasea\TaskManager\Job\NullJob');

        $task2 = new Task();
        $task2
            ->setName('task 2')
            ->setClassname('Alchemy\Phrasea\TaskManager\Job\NullJob');

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->persist($task2);
        self::$DI['app']['EM']->flush();

        $repository = self::$DI['app']['EM']->getRepository('Entities\Task');
        $this->assertSame(array($task2), $repository->findActiveTasks());

        $task1->setStatus(Task::STATUS_STARTED);

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->flush();

        $this->assertSame(array($task1, $task2), $repository->findActiveTasks());
    }
}
