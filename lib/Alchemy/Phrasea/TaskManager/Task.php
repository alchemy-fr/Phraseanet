<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\Phrasea\Model\Entities\Task as TaskEntity;
use Alchemy\TaskManager\TaskInterface;
use Symfony\Component\Process\Process;

class Task implements TaskInterface
{
    private $entity;
    private $name;
    private $iterations;
    private $process;

    public function __construct(TaskEntity $entity, $name, $iterations, Process $process)
    {
        $this->entity = $entity;
        $this->name = $name;
        $this->iterations = $iterations;
        $this->process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterations()
    {
        return $this->iterations;
    }

    /**
     * {@inheritdoc}
     */
    public function createProcess()
    {
        return clone $this->process;
    }

    /**
     * @return TaskEntity
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
