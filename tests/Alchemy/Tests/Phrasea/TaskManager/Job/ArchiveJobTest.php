<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\ArchiveJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ArchiveJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new ArchiveJob($this->createTranslatorMock());
    }
}
