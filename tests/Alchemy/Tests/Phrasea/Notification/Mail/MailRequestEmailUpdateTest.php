<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate
 */
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
