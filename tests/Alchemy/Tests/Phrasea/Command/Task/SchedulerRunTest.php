<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerRun;

class SchedulerRunTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager'] = $this->getMockBuilder('Alchemy\TaskManager\TaskManager')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['cli']['task-manager']->expects($this->once())
            ->method('addSubscriber')
            ->with($this->isInstanceOf('Alchemy\TaskManager\Event\TaskManagerSubscriber\LockFileSubscriber'));
        self::$DI['cli']['task-manager']->expects($this->once())
            ->method('start');

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new SchedulerRun();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
