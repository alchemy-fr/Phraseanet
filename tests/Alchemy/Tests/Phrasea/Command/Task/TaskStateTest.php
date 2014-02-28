<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskState;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskStateTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['monolog'] = self::$DI['cli']->share(function () {
            return $this->createMonologMock();
        });

        $input->expects($this->any())
                ->method('getArgument')
                ->with('task_id')
                ->will($this->returnValue(1));

        $command = new TaskState();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
