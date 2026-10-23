<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerRun;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SchedulerRunTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        self::$DI['cli']['task-manager'] = $this->createMock('Alchemy\TaskManager\TaskManager');
        self::$DI['cli']['task-manager']->expects($this->once())
            ->method('addSubscriber')
            ->with($this->isInstanceOf('Alchemy\TaskManager\Event\TaskManagerSubscriber\LockFileSubscriber'));
        self::$DI['cli']['task-manager']->expects($this->once())
            ->method('start');

        self::$DI['cli']['monolog'] = function () {
            return $this->createMonologMock();
        };

        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $command = new SchedulerRun();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
