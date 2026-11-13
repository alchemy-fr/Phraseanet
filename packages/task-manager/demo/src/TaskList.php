<?php

namespace Alchemy\TaskManager\Demo;

use Alchemy\TaskManager\TaskListInterface;

class TaskList implements TaskListInterface
{
    public function refresh()
    {
        return array(
            new RandomTask('task #1', INF),
            new RunJobTask('task #2', INF),
        );
    }
}
