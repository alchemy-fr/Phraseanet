<?php

namespace Alchemy\Worker\Tests;

use Alchemy\Worker\ProcessPool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ProcessPoolTest extends TestCase
{
    public function testWorkerProcessIsBuiltFromArguments()
    {
        $pool = new ProcessPool(2);
        $process = $pool->getWorkerProcess([PHP_BINARY, '-r', 'echo getcwd();'], sys_get_temp_dir());

        $this->assertInstanceOf(Process::class, $process);
        $this->assertSame(sys_get_temp_dir(), $process->getWorkingDirectory());

        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertSame(realpath(sys_get_temp_dir()), $process->getOutput());
    }

    public function testWorkingDirectoryDefaultsToCurrentDirectory()
    {
        $pool = new ProcessPool();
        $process = $pool->getWorkerProcess([PHP_BINARY, '-v']);

        $this->assertSame(getcwd(), $process->getWorkingDirectory());
    }
}
