<?php

namespace Alchemy\Worker\Tests;

use Alchemy\Worker\ProcessPool;
use Alchemy\Worker\WorkerInvoker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class WorkerInvokerTest extends TestCase
{
    public function testInvokeWorkerStartsTheWorkerCommand()
    {
        $process = new Process([PHP_BINARY, '-v']);

        $pool = $this->getMockBuilder(ProcessPool::class)
            ->onlyMethods(['getWorkerProcess'])
            ->getMock();
        $pool->expects($this->once())
            ->method('getWorkerProcess')
            ->with($this->callback(function (array $args) use (&$payloadFile) {
                $payloadFile = $args[4];

                return 'workers:run-worker' === $args[1]
                    && '-vv' === $args[2]
                    && 'type' === $args[3]
                    && '{"key":"value"}' === file_get_contents($args[4])
                    && '-e=test' === $args[5]
                    && '--preserve-payload' === $args[6];
            }))
            ->willReturn($process);

        $invoker = new WorkerInvoker($pool, 'test');
        $invoker->preservePayloads();
        $invoker->invokeWorker('type', '{"key":"value"}');
        $process->wait();

        unlink($payloadFile);

        $this->assertTrue($process->isSuccessful());
    }
}
