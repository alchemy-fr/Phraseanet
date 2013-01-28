<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailTest;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailTest
 */
class MailTestTest extends MailTestCase
{
    public function getMail()
    {
        return MailTest::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
    }
}
