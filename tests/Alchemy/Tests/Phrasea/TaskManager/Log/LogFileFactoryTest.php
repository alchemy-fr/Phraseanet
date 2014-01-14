<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\TaskManager\Log\LogFileFactory;
use Alchemy\Phrasea\Model\Entities\Task;

class LogFileFactoryTest extends \PhraseanetTestCase
{
    public function testForTask()
    {
        $task = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Task', 1);

        $root = __DIR__ . '/root';
        $factory = new LogFilefactory($root);
        $log = $factory->forTask($task);
        $this->assertInstanceOf('Alchemy\Phrasea\TaskManager\Log\TaskLogFile', $log);
        $this->assertSame($task, $log->getTask());
        $this->assertSame($root, $log->getRoot());
    }

    public function testForManager()
    {
        $root = __DIR__ . '/root';
        $factory = new LogFilefactory($root);
        $log = $factory->forManager();
        $this->assertInstanceOf('Alchemy\Phrasea\TaskManager\Log\ManagerLogFile', $log);
        $this->assertSame($root, $log->getRoot());
    }
}
