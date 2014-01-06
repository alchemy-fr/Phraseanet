<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Log;

use Alchemy\Phrasea\Model\Entities\Task;

class LogFileFactory
{
    private $root;

    public function __construct($root)
    {
        $this->root = $root;
    }

    /**
     * Generates a log file for a Task.
     *
     * @param Task $task
     *
     * @return LogFileInterface
     */
    public function forTask(Task $task)
    {
        return new TaskLogFile($this->root, $task);
    }

    /**
     * Generates a log file for a Manager.
     *
     * @return LogFileInterface
     */
    public function forManager()
    {
        return new ManagerLogFile($this->root);
    }
}
