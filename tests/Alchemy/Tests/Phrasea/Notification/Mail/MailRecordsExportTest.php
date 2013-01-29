<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRecordsExport;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRecordsExport
 */
class MailRecordsExportTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRecordsExport::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
