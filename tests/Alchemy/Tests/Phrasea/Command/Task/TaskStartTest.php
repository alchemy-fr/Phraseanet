<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskStart;
use Alchemy\Phrasea\Model\Entities\Task;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TaskStartTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->createMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        $input
                ->method('getArgument')
                ->with('task_id')
                ->will($this->returnValue(1));

        self::$DI['cli']['monolog'] = function () {
            return $this->createMonologMock();
        };

        $command = new TaskStart();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
