<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\TaskManager\Job\JobDataInterface;
use Alchemy\Phrasea\Model\Entities\Task;

class JobData implements JobDataInterface
{
    private $app;
    private $task;

    public function __construct(Application $app, Task $task)
    {
        $this->app = $app;
        $this->task = $task;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    public function __toString()
    {
        return sprintf('Task %d (%s)', $this->task->getId(), $this->task->getName());
    }
}
