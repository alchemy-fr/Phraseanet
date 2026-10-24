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

use Alchemy\TaskManager\Job\JobDataInterface;
use Alchemy\TaskManager\Job\JobInterface;
use Symfony\Contracts\EventDispatcher\Event;

class JobEvent extends Event
{
    private $job;
    private $data;

    public function __construct(JobInterface $job, JobDataInterface $data)
    {
        $this->job = $job;
        $this->data = $data;
    }

    /**
     * Returns the related job.
     *
     * @return JobInterface
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Returns the related data.
     *
     * @return JobDataInterface
     */
    public function getData()
    {
        return $this->data;
    }
}
