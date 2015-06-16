<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Notification\Deliverer;
use Alchemy\Phrasea\TaskManager\Job\FtpJob;

/**
 * @group functional
 * @group legacy
 */
class FtpJobTest extends JobTestCase
{
    protected function getJob()
    {
        return (new FtpJob($this->createTranslatorMock()))
            ->setDelivererLocator(function () {
                $this->getMockBuilder(Deliverer::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            });
    }
}
