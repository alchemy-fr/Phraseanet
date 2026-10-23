<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerPauseTasks;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SchedulerPauseTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager.status'] = $this->createMock('Alchemy\Phrasea\TaskManager\TaskManagerStatus');
        self::$DI['cli']['task-manager.status']->expects($this->once())
            ->method('stop');

        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['monolog'] = function () {
            return $this->createMonologMock();
        };

        $command = new SchedulerPauseTasks();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
