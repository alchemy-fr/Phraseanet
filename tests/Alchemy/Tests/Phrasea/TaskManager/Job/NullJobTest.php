<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\NullJob;

class NullJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new NullJob();
    }
}
