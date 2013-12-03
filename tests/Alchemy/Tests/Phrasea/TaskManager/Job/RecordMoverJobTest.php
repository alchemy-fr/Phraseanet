<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\RecordMoverJob;

class RecordMoverJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new RecordMoverJob(null, null, $this->createTranslatorMock());
    }
}
