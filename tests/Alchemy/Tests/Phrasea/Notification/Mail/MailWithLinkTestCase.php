<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

abstract class MailWithLinkTestCase extends MailTestCase
{
    public function testGetExpiration()
    {
        $this->assertInstanceOf('DateTime', $this->getMail()->getExpiration());
    }
}
