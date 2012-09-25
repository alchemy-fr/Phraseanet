<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\CLI;
use Alchemy\Phrasea\Core\Configuration;
use Symfony\Component\Console\Tester\CommandTester;

class module_console_taskStateTest extends PhraseanetPHPUnitAbstract
{

    /**
     * @covers module_console_taskState::execute
     */
    public function testExecute()
    {
        $application = new CLI('test', null, 'test');
        $application->command(new module_console_taskState('system:taskState'));

        $command = $application['console']->find('system:taskState');
        $commandTester = new CommandTester($command);

         // test a bad argument
        $commandTester->execute(array('command' => $command->getName(), 'task_id' => 'not_a_number'));
        $sentence = 'Argument must be an ID';
        $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

        $commandTester->execute(array('command' => $command->getName(), 'task_id' => 'not_a_number', '--short' => true));
        $sentence = 'bad_id';
        $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

        // test good tasks ids
        $task_manager = new task_manager($application);
        $tasks = $task_manager->getTasks();
        $tids = array();    // list known ids of tasks so we can generate a 'unknown id' later
        foreach ($tasks as $task) {
            $tids[] = $task->getID();
            $task = $task_manager->getTask($task->getID());
            $state = $task->getState();
            $pid = $task->getPID();

            $commandTester->execute(array('command' => $command->getName(), 'task_id' => $task->getID()));
            $sentence = sprintf('Task %d is %s', $task->getID(), $state);
            $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

            $commandTester->execute(array('command' => $command->getName(), 'task_id' => $task->getID(), '--short' => true));
            $sentence = sprintf('%s(%s)', $state, ($pid !== NULL ? $pid : ''));
            $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);
        }

        // test an unknown id
        for ($badid = 999; in_array($badid, $tids); $badid += 10) {
            ;
        }
        /* we may not test the 'long' error message since it comes from the upper level exception and might be translated
          $commandTester->execute(array('command' => $command->getName(), 'task_id' => $badid));
          $sentence = sprintf('Unknown task_id %d', $task->getID());
          $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);
         */
        $commandTester->execute(array('command' => $command->getName(), 'task_id' => $badid, '--short' => true));
        $sentence = 'unknown_id';
        $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

    }
}
