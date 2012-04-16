<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\Console\Tester\CommandTester;
use \Symfony\Component\Console\Application;
class module_console_systemTemplateGeneratorTest extends PHPUnit_Framework_TestCase
{
  public function testExecute()
  {
    // mock the Kernel or create one depending on your needs
    $application = new Application();
    $application->add(new module_console_systemTemplateGenerator('system:templateGenerator'));

    $command = $application->find('system:templateGenerator');
    $commandTester = new CommandTester($command);
    $commandTester->execute(array('command' => $command->getName()));

    $last_line = array_pop(explode("\n", trim($commandTester->getDisplay())));

    $this->assertTrue(strpos($last_line, 'templates failed') === false, 'Some templates failed');
    $this->assertTrue(strpos($last_line, 'templates generated') !== true, 'Some templates have been generated');
  }


}
