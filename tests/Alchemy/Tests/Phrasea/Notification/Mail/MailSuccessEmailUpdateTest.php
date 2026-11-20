<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailSuccessEmailUpdateTest extends MailTestCase
{
    public function getMail()
    {
        return MailSuccessEmailUpdate::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
    }
}
