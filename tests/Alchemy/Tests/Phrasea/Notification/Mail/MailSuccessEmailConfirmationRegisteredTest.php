<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailSuccessEmailConfirmationRegisteredTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailSuccessEmailConfirmationRegistered::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
