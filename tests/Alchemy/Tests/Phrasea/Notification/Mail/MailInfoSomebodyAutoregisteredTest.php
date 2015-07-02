<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoSomebodyAutoregistered
 */
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
