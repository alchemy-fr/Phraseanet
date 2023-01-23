<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailCheck;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailCheck
 */
class MailCheckTest extends MailTestCase
{
    public function getMail()
    {
        return MailCheck::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
    }
}
