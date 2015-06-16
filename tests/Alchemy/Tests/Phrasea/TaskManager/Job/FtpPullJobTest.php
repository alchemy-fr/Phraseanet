<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\FtpPullJob;

/**
 * @group functional
 * @group legacy
 */
class FtpPullJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new FtpPullJob($this->createTranslatorMock());
    }
}
