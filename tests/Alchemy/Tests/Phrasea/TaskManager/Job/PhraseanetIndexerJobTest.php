<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\PhraseanetIndexerJob;

class PhraseanetIndexerJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new PhraseanetIndexerJob(null, null, $this->createTranslatorMock());
    }
}
