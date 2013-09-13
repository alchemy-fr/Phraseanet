<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\ArchiveJob;

class ArchiveJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new ArchiveJob();
    }
}
