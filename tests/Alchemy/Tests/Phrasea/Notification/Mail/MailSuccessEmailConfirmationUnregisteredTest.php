<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailSuccessEmailConfirmationUnregisteredTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailSuccessEmailConfirmationUnregistered::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
