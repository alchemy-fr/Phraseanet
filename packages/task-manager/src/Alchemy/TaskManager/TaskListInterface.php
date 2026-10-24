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

interface TaskListInterface
{
    /**
     * Returns an up-to-date array of TaskInterface that should be synced by
     * the TaskManager.
     *
     * @return TaskInterface[]
     */
    public function refresh();
}
