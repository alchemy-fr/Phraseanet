<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\RecordMoverJob;

/**
 * @group functional
 * @group legacy
 */
class RecordMoverJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new RecordMoverJob($this->createTranslatorMock());
    }
}
