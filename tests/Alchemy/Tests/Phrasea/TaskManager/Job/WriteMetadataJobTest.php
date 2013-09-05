<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\WriteMetadataJob;
use Alchemy\Phrasea\TaskManager\Job\JobData;
use Entities\Task;

class WriteMetadataJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new WriteMetadataJob();
    }
    
    public function doTestRun()
    {
        $job = $this->getJob();
        $task = new Task();
        $task->setSettings($job->getEditor()->getDefaultSettings());
        $job->singleRun(new JobData(self::$DI['app'], $task));
    }
}
