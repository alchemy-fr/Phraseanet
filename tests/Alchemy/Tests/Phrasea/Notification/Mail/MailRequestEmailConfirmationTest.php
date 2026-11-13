<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
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
