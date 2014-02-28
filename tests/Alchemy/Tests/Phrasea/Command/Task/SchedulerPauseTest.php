<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerPauseTasks;

class SchedulerPauseTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager.status'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\TaskManagerStatus')
            ->disableOriginalConstructor()
            ->getMock();
        self::$DI['cli']['task-manager.status']->expects($this->once())
            ->method('stop');

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () {
            return $this->createMonologMock();
        });

        $command = new SchedulerPauseTasks();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
