<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerResumeTasks;

class SchedulerResumeTasksTest extends \PhraseanetPHPUnitAbstract
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager.status'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\TaskManagerStatus')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['cli']['task-manager.status']->expects($this->once())
            ->method('start');

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new SchedulerResumeTasks();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
