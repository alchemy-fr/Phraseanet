<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\FtpPullJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FtpPullJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new FtpPullJob($this->createTranslatorMock());
    }
}
