<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation
 */
class MailRequestEmailConfirmationTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRequestEmailConfirmation::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
