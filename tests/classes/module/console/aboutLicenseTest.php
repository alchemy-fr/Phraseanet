<?php

use Alchemy\Phrasea\CLI;
use Symfony\Component\Console\Tester\CommandTester;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class module_console_aboutLicenseTest extends \PhraseanetTestCase
{
    /**
     * @var module_console_aboutLicense
     */
    protected $object;

    public function testExecute()
    {
        $application = new CLI('test', null, 'test');
        $application->command(new module_console_aboutLicense('about:license'));

        $command = $application['console']->find('about:license');

        // TODO: on builder mode
//        $commandTester = new CommandTester($command);
//        $commandTester->execute(['command' => $command->getName()]);

//        $this->assertEquals(
//            trim(file_get_contents($application['root.path'] . '/LICENSE'))
//            , trim($commandTester->getDisplay())
//        );
    }
}
