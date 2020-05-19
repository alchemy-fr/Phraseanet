<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\Factory;

use Alchemy\Phrasea\WorkerManager\Worker\WorkerInterface;

class CallableWorkerFactory implements WorkerFactoryInterface
{
    /**
     * @var callable
     */
    private $factory;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return WorkerInterface
     */
    public function createWorker()
    {
        $factory = $this->factory;
        $worker = $factory();

        if (! $worker instanceof WorkerInterface) {
            throw new \RuntimeException('Invalid worker created, expected an instance of \Alchemy\Phrasea\WorkerManager\Worker\WorkerInterface');
        }

        return $worker;
    }
}