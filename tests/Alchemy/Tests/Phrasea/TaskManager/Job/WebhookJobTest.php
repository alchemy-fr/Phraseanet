<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\WebhookJob;

class WebhookJobTestJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new WebhookJob(null, null, $this->createTranslatorMock());
    }
}
