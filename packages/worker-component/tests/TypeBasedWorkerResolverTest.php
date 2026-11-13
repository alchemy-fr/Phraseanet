<?php

namespace Alchemy\Worker\Tests;

use Alchemy\Worker\CallableWorkerFactory;
use Alchemy\Worker\TypeBasedWorkerResolver;
use Alchemy\Worker\Worker;
use PHPUnit\Framework\TestCase;

class TypeBasedWorkerResolverTest extends TestCase
{
    public function testWorkersAreResolvedByType()
    {
        $calls = 0;
        $worker = $this->createMock(Worker::class);
        $factory = new CallableWorkerFactory(function () use ($worker, &$calls) {
            $calls++;

            return $worker;
        });

        $resolver = new TypeBasedWorkerResolver();
        $resolver->setFactory('type', $factory);

        $this->assertSame(['type' => $factory], $resolver->getFactories());
        $this->assertTrue($resolver->hasWorker('type', []));
        $this->assertFalse($resolver->hasWorker('other', []));
        $this->assertSame($worker, $resolver->getWorker('type', []));
        $this->assertSame($worker, $resolver->getWorker('type', []));
        $this->assertSame(1, $calls);
    }

    public function testUnknownTypeThrowsAnException()
    {
        $resolver = new TypeBasedWorkerResolver();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid worker type requested: unknown');
        $resolver->getWorker('unknown', []);
    }
}
