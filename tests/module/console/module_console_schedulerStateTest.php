<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\Console\Tester\CommandTester;
use \Symfony\Component\Console\Application;
class module_console_schedulerStateTest extends PHPUnit_Framework_TestCase
{

  public function testExecute()
  {
    // mock the Kernel or create one depending on your needs
    $application = new Application();
    $application->add(new module_console_schedulerState('system:schedulerState'));

    $command = $application->find('system:schedulerState');
    $commandTester = new CommandTester($command);
    $commandTester->execute(array('command' => $command->getName()));

    $task_manager = new task_manager(appbox::get_instance(\bootstrap::getCore()));
    $state = $task_manager->get_scheduler_state();

    $sentence = sprintf('Scheduler is %s', $state['state']);
    $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

  }


}
