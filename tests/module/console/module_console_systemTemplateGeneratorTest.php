<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\CLI;
use Symfony\Component\Console\Tester\CommandTester;

class module_console_systemTemplateGeneratorTest extends PHPUnit_Framework_TestCase
{

    public function testExecute()
    {
        $application = new CLI('test', null, 'test');
        $application->command(new module_console_systemTemplateGenerator('system:templateGenerator'));

        $command = $application['console']->find('system:templateGenerator');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $data = explode("\n", trim($commandTester->getDisplay()));
        $last_line = array_pop($data);

        $this->assertTrue(strpos($last_line, 'templates failed') === false, 'Some templates failed');
        $this->assertTrue(strpos($last_line, 'templates generated') !== true, 'Some templates have been generated');
    }
}
