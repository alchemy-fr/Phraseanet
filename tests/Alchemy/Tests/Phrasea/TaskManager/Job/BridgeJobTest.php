<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\BridgeJob;

class BridgeJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new BridgeJob();
    }
}
