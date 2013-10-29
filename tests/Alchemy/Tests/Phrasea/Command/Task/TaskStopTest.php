<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskStop;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskStopTest extends \PhraseanetPHPUnitAbstract
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

        $command = new TaskStop();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
