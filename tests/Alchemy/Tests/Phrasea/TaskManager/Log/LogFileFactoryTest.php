<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\TaskManager\Log\LogFileFactory;
use Alchemy\Phrasea\Model\Entities\Task;

class LogFilefactorytest extends \PhraseanetPHPUnitAbstract
{
    public function testForTask()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');

        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

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
