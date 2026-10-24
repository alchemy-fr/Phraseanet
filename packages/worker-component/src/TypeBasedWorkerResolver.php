<?php

/*
 * This file is part of alchemy/worker-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Worker;

class TypeBasedWorkerResolver implements WorkerResolver
{

    /**
     * @var Worker[]
     */
    private $workers = [];

    /**
     * @var WorkerFactory[]
     */
    private $factories = [];

    /**
     * @param string $messageType
     * @param WorkerFactory $workerFactory
     */
    public function setFactory($messageType, WorkerFactory $workerFactory)
    {
        $this->factories[$messageType] = $workerFactory;
    }

    /**
     * @return WorkerFactory[]
     */
    public function getFactories()
    {
        return $this->factories;
    }

    /**
     * @param string $messageType
     * @param array $message
     * @return bool
     */
    public function hasWorker($messageType, array $message)
    {
        return (isset($this->factories[$messageType]));
    }

    /**
     * @param string $messageType
     * @param array $message
     * @return Worker
     */
    public function getWorker($messageType, array $message)
    {
        if (isset($this->workers[$messageType])) {
            return $this->workers[$messageType];
        }

        if (isset($this->factories[$messageType])) {
            return $this->workers[$messageType] = $this->factories[$messageType]->createWorker();
        }

        throw new \RuntimeException('Invalid worker type requested: ' . $messageType);
    }
}
