<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\RecordMoverJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RecordMoverJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new RecordMoverJob($this->createTranslatorMock());
    }
}
