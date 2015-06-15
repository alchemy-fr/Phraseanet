<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\NullJob;

/**
 * @group functional
 * @group legacy
 */
class NullJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new NullJob($this->createTranslatorMock());
    }
}
