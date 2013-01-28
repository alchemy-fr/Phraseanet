<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder
 */
class MailInfoValidationReminderTest extends MailWithLinkTestCase
{
    /**
     * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationReminder::setTitle
     */
    public function testSetTitle()
    {
        $this->assertContainsString('Hulk Hogan', $this->getMail()->getSubject());
    }

    public function testShouldThrowALogicExceptionIfNoTitleProvided()
    {
        $mail =  MailInfoValidationReminder::create(
            $this->getApp(),
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
            $this->getApp(),
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
