<?php

namespace Alchemy\Tests\Phrasea\Command\Task;

use Alchemy\Phrasea\Command\Task\SchedulerState;

class SchedulerStateTest extends \PhraseanetTestCase
{
    public function testRunWithoutProblems()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new SchedulerState();
        $command->setContainer(self::$DI['cli']);
        $command->execute($input, $output);
    }
}
