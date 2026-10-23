<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerState;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SchedulerStateTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->createStub('Symfony\Component\Console\Input\InputInterface');
        $output = $this->createStub('Symfony\Component\Console\Output\OutputInterface');

        self::$DI['cli']['monolog'] = function () {
            return $this->createMonologMock();
        };

        $command = new SchedulerState();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
