<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\WebhookJob;

/**
 * @group functional
 * @group legacy
 */
class WebhookJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new WebhookJob($this->createTranslatorMock(), null, null);
    }
}
