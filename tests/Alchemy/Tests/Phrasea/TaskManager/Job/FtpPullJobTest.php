<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\FtpPullJob;

class FtpPullJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new FtpPullJob();
    }
}
