<?php

namespace Alchemy\Tests\Phrasea\Application;

use Symfony\Component\Process\Process;

class ConsoleAPITest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideConsoleNames
     */
    public function testThatCommandsExitWithZero($console)
    {
        $process = new Process(__DIR__ . '/../../../../../bin/'.$console);
        $process->run();

        $this->assertSame(0, $process->getExitCode());
    }

    public function provideConsoleNames()
    {
        return array(
            array('console'),
            array('setup'),
            array('developer'),
        );
    }
}
