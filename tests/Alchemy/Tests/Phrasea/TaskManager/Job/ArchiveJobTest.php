<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\ArchiveJob;

/**
 * @group functional
 * @group legacy
 */
class ArchiveJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new ArchiveJob($this->createTranslatorMock());
    }
}
