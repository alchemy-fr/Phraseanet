<?php

namespace Alchemy\Worker\Tests;

use Alchemy\Worker\CallableWorkerFactory;
use Alchemy\Worker\Worker;
use PHPUnit\Framework\TestCase;

class CallableWorkerFactoryTest extends TestCase
{
    public function testFactoryReturnsTheCreatedWorker()
    {
        $worker = $this->createMock(Worker::class);
        $factory = new CallableWorkerFactory(function () use ($worker) {
            return $worker;
        });

        $this->assertSame($worker, $factory->createWorker());
    }

    public function testFactoryRejectsInvalidWorkers()
    {
        $factory = new CallableWorkerFactory(function () {
            return new \stdClass();
        });

        $this->expectException(\RuntimeException::class);
        $factory->createWorker();
    }
}
