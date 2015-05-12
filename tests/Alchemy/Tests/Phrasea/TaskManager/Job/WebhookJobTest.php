<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\WebhookJob;

class WebhookJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new WebhookJob($this->createTranslatorMock(), null, null);
    }
}
