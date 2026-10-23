<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessAccountDelete;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessAccountDelete
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailSuccessAccountDeleteTest extends MailTestCase
{
    public function getMail()
    {
        return MailSuccessAccountDelete::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
    }
}
