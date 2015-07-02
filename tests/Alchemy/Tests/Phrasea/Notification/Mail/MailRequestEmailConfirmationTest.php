<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation
 */
class MailRequestEmailConfirmationTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRequestEmailConfirmation::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
