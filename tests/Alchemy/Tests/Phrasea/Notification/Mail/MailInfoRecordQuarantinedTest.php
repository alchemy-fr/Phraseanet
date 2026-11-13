<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoRecordQuarantined
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
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
