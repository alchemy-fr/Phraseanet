<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\TaskManager\Log\TaskLogFile;
use Entities\Task;

class TaskLogFileTest extends LogFileTestCase
{
    public function testGetters()
    {
        $task = new Task();
        $root = '/path/to/root';

        $logfile = new TaskLogFile($root, $task);
        $this->assertSame($task, $logfile->getTask());
        $this->assertSame($root, $logfile->getRoot());
    }

    protected function getLogFile($root)
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setClassname('Alchemy\Phrasea\TaskManager\Job\NullJob');

        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        return new TaskLogFile($root, $task);
    }
}
