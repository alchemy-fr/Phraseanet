<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessAccessRequest;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessAccessRequest
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailSuccessAccessRequestTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailSuccessAccessRequest::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
