<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailInfoSomebodyAutoregisteredTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailInfoSomebodyAutoregistered::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
