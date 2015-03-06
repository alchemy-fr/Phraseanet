<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\TaskManager\Log\TaskLogFile;
use Alchemy\Phrasea\Model\Entities\Task;

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
        $task = self::$DI['app']['orm.em']->find('Phraseanet:Task', 1);

        return new TaskLogFile($root, $task);
    }
}
