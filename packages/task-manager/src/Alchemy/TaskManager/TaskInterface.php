<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager;

use Symfony\Component\Process\Process;

interface TaskInterface
{
    /**
     * Gets the name of the task.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets the number of iterations the process should run.
     *
     * @return integer
     */
    public function getIterations();

    /**
     * Creates a processable object for the TaskManager.
     *
     * @return Process
     */
    public function createProcess();
}
