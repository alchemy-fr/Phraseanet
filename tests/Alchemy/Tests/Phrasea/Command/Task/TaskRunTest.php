<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskRun;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskRunTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $task = new Task();
        $task
            ->setName('Task')
            ->setJobId('Null');

        self::$DI['cli']['EM']->persist($task);
        self::$DI['cli']['EM']->flush();

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $input->expects($this->any())
                ->method('getArgument')
                ->with('task_id')
                ->will($this->returnValue($task->getId()));

        $command = new TaskRun();
        $command->setContainer(self::$DI['cli']);

        $job = $this->getMock('Alchemy\Phrasea\TaskManager\Job\JobInterface');

        self::$DI['cli']['task-manager.job-factory'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\Job\Factory')
            ->disableOriginalConstructor()
            ->getMock();
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
