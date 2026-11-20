<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailRequestEmailUpdateTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRequestEmailUpdate::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
