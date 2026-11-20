<?php

/*
 * This file is part of phrasea-4.1.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Worker;

class CallableWorkerFactory implements WorkerFactory
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
     * @return Worker
     */
    public function createWorker()
    {
        $factory = $this->factory;
        $worker = $factory();

        if (! $worker instanceof Worker) {
            throw new \RuntimeException('Invalid worker created, expected an instance of \Alchemy\Worker\Worker');
        }

        return $worker;
    }
}
