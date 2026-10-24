<?php

namespace Alchemy\TaskManager\Test;

use Alchemy\TaskManager\TaskListInterface;

abstract class TaskListTestCase extends \PHPUnit\Framework\TestCase
{
    public function testThatRefreshReturnsAnArrayOfTaskInterface()
    {
        $list = $this->getTaskList();
        foreach ($list->refresh() as $task) {
            $this->assertInstanceOf('Alchemy\TaskManager\TaskInterface', $task);
        }
    }

    /**
     * @return TaskListInterface
     */
    abstract protected function getTaskList();
}
