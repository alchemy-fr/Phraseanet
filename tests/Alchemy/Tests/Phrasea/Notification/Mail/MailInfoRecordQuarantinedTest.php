<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined
 */
class MailInfoRecordQuarantinedTest extends MailTestCase
{
    public function getMail()
    {
        return MailInfoRecordQuarantined::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
    }
}
