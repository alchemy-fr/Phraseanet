<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\CLI;
use Symfony\Component\Console\Tester\CommandTester;

class module_console_schedulerStateTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers module_console_schedulerState::execute
     */
    public function testExecute()
    {
        $application = new CLI('test', null, 'test');
        $application->command(new module_console_schedulerState('system:schedulerState'));

        $command = $application['console']->find('system:schedulerState');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $task_manager = new task_manager($application);
        $state = $task_manager->getSchedulerState();

        $sentence = sprintf('Scheduler is %s', $state['status']);
        $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

        $commandTester->execute(array('command' => $command->getName(), '--short'=>true));
        $task_manager = new task_manager($application);
        $state = $task_manager->getSchedulerState();

        $sentence = sprintf('%s(%s)', $state['status'], $state['pid']);
        $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

    }
}
