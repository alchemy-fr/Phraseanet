<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\FtpJob;

class FtpJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new FtpJob();
    }
}
