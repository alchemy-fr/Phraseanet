<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskRun;
use Alchemy\Phrasea\Model\Entities\Task;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TaskRunTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $input
                ->method('getArgument')
                ->with('task_id')
                ->will($this->returnValue(1));

        $command = new TaskRun();
        $command->setContainer(self::$DI['cli']);

        $job = $this->createMock('Alchemy\Phrasea\TaskManager\Job\JobInterface');

        self::$DI['cli']['task-manager.job-factory'] = $this->createStub('Alchemy\Phrasea\TaskManager\Job\Factory');
        self::$DI['cli']['task-manager.job-factory']->expects($this->once())
                ->method('create')
                ->will($this->returnValue($job));

        $job->expects($this->once())
            ->method('run');
        $job->expects($this->exactly(2))
            ->method('addSubscriber');

        $command->execute($input, $output);
    }
}
