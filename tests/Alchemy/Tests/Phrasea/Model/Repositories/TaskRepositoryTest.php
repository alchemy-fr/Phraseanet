<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Task;

class TaskRepositoryTest extends \PhraseanetTestCase
{
    public function testFindActiveTask()
    {
        $task1 = self::$DI['app']['EM']->find('Phraseanet:Task', 1);
        $task1->setStatus(Task::STATUS_STOPPED);
        $task2 = self::$DI['app']['EM']->find('Phraseanet:Task', 2);

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->flush();

        $repository = self::$DI['app']['EM']->getRepository('Phraseanet:Task');
        $this->assertSame([$task2], $repository->findActiveTasks());

        $task1->setStatus(Task::STATUS_STARTED);

        self::$DI['app']['EM']->persist($task1);
        self::$DI['app']['EM']->flush();

        $this->assertSame([$task1, $task2], $repository->findActiveTasks());
    }
}
