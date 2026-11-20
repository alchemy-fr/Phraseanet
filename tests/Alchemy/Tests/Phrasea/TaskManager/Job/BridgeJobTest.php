<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\BridgeJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BridgeJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new BridgeJob($this->createTranslatorMock());
    }
}
