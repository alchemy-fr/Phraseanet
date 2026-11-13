<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\NullJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class NullJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new NullJob($this->createTranslatorMock());
    }
}
