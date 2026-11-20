<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event;

use Alchemy\TaskManager\TaskManager;
use Symfony\Contracts\EventDispatcher\Event;

class TaskManagerEvent extends Event
{
    private $manager;

    public function __construct(TaskManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Returns the manager.
     *
     * @return TaskManager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
