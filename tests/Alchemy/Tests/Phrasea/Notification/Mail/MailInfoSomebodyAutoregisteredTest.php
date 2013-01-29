<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered
 */
class MailInfoSomebodyAutoregisteredTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailInfoSomebodyAutoregistered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
