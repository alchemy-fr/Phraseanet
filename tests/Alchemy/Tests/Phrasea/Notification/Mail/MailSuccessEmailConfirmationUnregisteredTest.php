<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered
 */
class MailSuccessEmailConfirmationUnregisteredTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailSuccessEmailConfirmationUnregistered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
