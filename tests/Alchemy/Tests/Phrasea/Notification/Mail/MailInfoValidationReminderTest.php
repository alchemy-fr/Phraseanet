<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailInfoValidationReminderTest extends MailWithLinkTestCase
{
    public function testSetTitle()
    {
        $this->assertEquals('Reminder : validate \'%title%\'', $this->getMail()->getSubject());
    }

    public function testShouldThrowALogicExceptionIfNoTitleProvided()
    {
        $mail =  MailInfoValidationReminder::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailInfoValidationReminder::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setTitle('Hulk hogan');

        return $mail;
    }
}
