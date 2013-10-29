<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskStart;
use Entities\Task;

class TaskStartTest extends \PhraseanetPHPUnitAbstract
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

        $command = new TaskStart();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
