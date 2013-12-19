<?php

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\TaskManager\TaskList;

class TaskListTest extends \PhraseanetTestCase
{
    public function testThatRefreshReturnsAnArrayOfTaskInterface()
    {
        $list = $this->getTaskList();

        $data = $list->refresh();
        $this->assertCount(2, $data);

        foreach ($data as $task) {
            $this->assertInstanceOf('Alchemy\TaskManager\TaskInterface', $task);
        }
    }

    public function getTaskList()
    {
        $task3 = new Task();
        $task3
            ->setName('task 3')
            ->setStatus(Task::STATUS_STOPPED)
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');

        self::$DI['app']['EM']->persist($task3);
        self::$DI['app']['EM']->flush();

        return new TaskList(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Task'), self::$DI['app']['root.path'], '/path/to/php', '/path/to/php-conf');
    }

    public function testThatProcessHaveNoTimeout()
    {
        $list = $this->getTaskList();
        $data = $list->refresh();

        foreach ($data as $task) {
            $this->assertEquals(0, $task->createProcess()->getTimeout());
        }
    }

    public function testThatProcessHaveTheirIdsAsNames()
    {
        $list = $this->getTaskList();
        $data = $list->refresh();

        foreach ($data as $task) {
            $this->assertEquals($task->getEntity()->getId(), $task->getName());
        }
    }

    public function testGeneratedProcesses()
    {
        $list = $this->getTaskList();
        $n = 1;
        foreach ($list->refresh() as $task) {
            $this->assertEquals("'/path/to/php' '-c' '/path/to/php-conf' '-f' '".self::$DI['app']['root.path']."/bin/console' '--' '-q' 'task-manager:task:run' '".$n."' '--listen-signal' '--max-duration' '1800' '--max-memory' '134217728'", $task->createProcess()->getCommandLine());
            $n++;
        }
        $this->assertSame(3, $n);
    }
}
