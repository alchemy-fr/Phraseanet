<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessAccountDelete;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessAccountDelete
 */
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
