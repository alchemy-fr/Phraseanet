<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\TaskManager\Job\FtpJob;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FtpJobTest extends JobTestCase
{
    protected function getJob()
    {
        return (new FtpJob($this->createTranslatorMock()))
            ->setDelivererLocator(function () {
                $this->createMock(Deliverer::class);
            });
    }
}
