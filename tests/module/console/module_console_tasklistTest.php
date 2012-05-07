<?php

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class module_console_tasklistTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers module_console_tasklist::execute
     */
    public function testExecute()
    {
        // mock the Kernel or create one depending on your needs
        $application = new Application();
        $application->add(new module_console_tasklist('task:list'));

        $command = $application->find('task:list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $task_manager = new task_manager(appbox::get_instance(\bootstrap::getCore()));
        $lines = explode("\n", trim($commandTester->getDisplay()));

        if (count($task_manager->getTasks()) > 0) {
            $this->assertEquals(count($task_manager->getTasks()), count($lines));
            foreach ($task_manager->getTasks() as $task) {
                $this->assertTrue(strpos($commandTester->getDisplay(), $task->get_title()) !== false);
            }
        } else {
            $this->assertEquals(1, count($lines));
        }
    }
}
