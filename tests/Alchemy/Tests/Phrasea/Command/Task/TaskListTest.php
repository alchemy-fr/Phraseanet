<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskList;

class TaskListTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $this->insertTwoTasks();

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new TaskList();
        $command->setContainer(self::$DI['cli']);

        $application = new \Symfony\Component\Console\Application();
        $application->add($command);

        $setupCommand = $application->find('task-manager:task:list');
        $setupCommand->execute($input, $output);
    }
}
