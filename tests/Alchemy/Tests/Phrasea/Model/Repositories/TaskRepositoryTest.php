<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Task;

class TaskRepositoryTest extends \PhraseanetTestCase
{
    public function testFindActiveTask()
    {
        $task1 = self::$DI['app']['orm.em']->find('Phraseanet:Task', 1);
        $task1->setStatus(Task::STATUS_STOPPED);
        $task2 = self::$DI['app']['orm.em']->find('Phraseanet:Task', 2);

        self::$DI['app']['orm.em']->persist($task1);
        self::$DI['app']['orm.em']->flush();

        $repository = self::$DI['app']['orm.em']->getRepository('Phraseanet:Task');
        $this->assertSame([$task2], $repository->findActiveTasks());

        $task1->setStatus(Task::STATUS_STARTED);

        self::$DI['app']['orm.em']->persist($task1);
        self::$DI['app']['orm.em']->flush();

        $this->assertSame([$task1, $task2], $repository->findActiveTasks());
    }
}
