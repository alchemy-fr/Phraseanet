<?php

namespace Alchemy\TaskManager\Demo;

use Alchemy\TaskManager\Job\AbstractJob;
use Alchemy\TaskManager\Job\JobDataInterface;

class Job extends AbstractJob
{
    protected function doRun(JobDataInterface $data)
    {
        $time = microtime(true);
        while (microtime(true) < $time + 1) {
            usleep(10000);
        }
    }
}
