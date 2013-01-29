<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered
 */
class MailSuccessEmailConfirmationRegisteredTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailSuccessEmailConfirmationRegistered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
