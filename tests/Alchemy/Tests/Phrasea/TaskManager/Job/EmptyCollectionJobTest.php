<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\EmptyCollectionJob;

/**
 * @group functional
 * @group legacy
 */
class EmptyCollectionJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new EmptyCollectionJob($this->createTranslatorMock());
    }
}
