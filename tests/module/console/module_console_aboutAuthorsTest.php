<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\CLI;
use Symfony\Component\Console\Tester\CommandTester;

class module_console_aboutAuthorsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var module_console_aboutAuthors
     */
    protected $object;

    /**
     * @covers module_console_aboutAuthors::execute
     */
    public function testExecute()
    {
        $application = new CLI('test', null, 'test');
        $application->command(new module_console_aboutAuthors('about:authors'));

        $command = $application['console']->find('about:authors');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertEquals(
            trim(file_get_contents(__DIR__ . '/../../../AUTHORS'))
            , trim($commandTester->getDisplay())
        );
    }
}
