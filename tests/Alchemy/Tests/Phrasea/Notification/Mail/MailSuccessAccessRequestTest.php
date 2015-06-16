<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessAccessRequest;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessAccessRequest
 */
class MailSuccessAccessRequestTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailSuccessAccessRequest::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
