<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate
 */
class MailRequestEmailUpdateTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRequestEmailUpdate::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
