<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\TaskStop;
use Alchemy\Phrasea\Model\Entities\Task;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TaskStopTest extends \PhraseanetTestCase
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

        $command = new TaskStop();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
