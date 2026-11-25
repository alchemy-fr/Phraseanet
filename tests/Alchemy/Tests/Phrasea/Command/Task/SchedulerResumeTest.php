<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerResumeTasks;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SchedulerResumeTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager.status'] = $this->createMock('Alchemy\Phrasea\TaskManager\TaskManagerStatus');
        self::$DI['cli']['task-manager.status']->expects($this->once())
            ->method('start');

        self::$DI['cli']['monolog'] = function () {
            return $this->createMonologMock();
        };

        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $command = new SchedulerResumeTasks();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
