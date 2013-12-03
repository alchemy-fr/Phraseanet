<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\EmptyCollectionJob;

class EmptyCollectionJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new EmptyCollectionJob(null, null, $this->createTranslatorMock());
    }
}
