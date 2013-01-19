<?php

use Alchemy\Phrasea\CLI;
use Symfony\Component\Console\Tester\CommandTester;

class module_console_aboutLicenseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var module_console_aboutLicense
     */
    protected $object;

    /**
     * @covers module_console_aboutAuthors::execute
     */
    public function testExecute()
    {
        $application = new CLI('test', null, 'test');
        $application->command(new module_console_aboutLicense('about:license'));

        $command = $application['console']->find('about:license');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertEquals(
            trim(file_get_contents(__DIR__ . '/../../../../LICENSE'))
            , trim($commandTester->getDisplay())
        );
    }
}
