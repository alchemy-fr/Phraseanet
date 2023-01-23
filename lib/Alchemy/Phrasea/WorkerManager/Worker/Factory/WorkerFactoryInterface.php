<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\Factory;

use Alchemy\Phrasea\WorkerManager\Worker\WorkerInterface;

interface WorkerFactoryInterface
{
    /**
     * @return WorkerInterface
     */
    public function createWorker();
}
