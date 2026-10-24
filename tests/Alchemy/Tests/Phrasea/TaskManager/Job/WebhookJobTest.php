<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Job\WebhookJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class WebhookJobTest extends JobTestCase
{
    protected function getJob()
    {
        return new WebhookJob($this->createTranslatorMock(), null, null);
    }
}
