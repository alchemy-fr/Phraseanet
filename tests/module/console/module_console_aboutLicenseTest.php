<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\Console\Tester\CommandTester;
use \Symfony\Component\Console\Application;

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
        // mock the Kernel or create one depending on your needs
        $application = new Application();
        $application->add(new module_console_aboutLicense('about:license'));

        $command = $application->find('about:license');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertEquals(
            trim(file_get_contents(__DIR__ . '/../../../LICENSE'))
            , trim($commandTester->getDisplay())
        );
    }
}
