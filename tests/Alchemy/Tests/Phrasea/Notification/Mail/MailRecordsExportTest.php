<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRecordsExport;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRecordsExport
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailRecordsExportTest extends MailWithLinkTestCase
{
    public function getMail()
    {
        return MailRecordsExport::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );
    }
}
