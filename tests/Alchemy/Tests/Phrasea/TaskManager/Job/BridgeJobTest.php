<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\BridgeJob;

/**
 * @group functional
 * @group legacy
 */
class BridgeJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new BridgeJob($this->createTranslatorMock());
    }
}
