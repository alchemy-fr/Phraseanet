<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Task;

class TaskRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFindActiveTask()
    {
        $task1 = new Task();
        $task1
            ->setName('task 1')
            ->setStatus(Task::STATUS_STOPPED)
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');

        $task2 = new Task();
        $task2
            ->setName('task 2')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->persist($task2);
        self::$DI['app']['EM']->flush();

        $repository = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task');
        $this->assertSame([$task2], $repository->findActiveTasks());

        $task1->setStatus(Task::STATUS_STARTED);

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->flush();

        $this->assertSame([$task1, $task2], $repository->findActiveTasks());
    }
}
