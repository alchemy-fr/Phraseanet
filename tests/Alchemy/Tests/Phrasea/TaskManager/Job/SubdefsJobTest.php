<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\SubdefsJob;
use Alchemy\Phrasea\TaskManager\Job\JobData;
use Entities\Task;

class SubdefsJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new SubdefsJob();
    }
    
    public function doTestRun()
    {
        $job = $this->getJob();
        $task = new Task();
        $task->setSettings($job->getEditor()->getDefaultSettings());
        $job->singleRun(new JobData(self::$DI['app'], $task));
    }
}
