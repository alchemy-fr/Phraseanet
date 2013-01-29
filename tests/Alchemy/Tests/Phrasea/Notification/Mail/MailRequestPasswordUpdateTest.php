<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate
 */
class MailRequestPasswordUpdateTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRequestPasswordUpdate::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
