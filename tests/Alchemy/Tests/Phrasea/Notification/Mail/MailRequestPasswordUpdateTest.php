<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Alchemy\Phrasea\Exception\LogicException;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate
 */
class MailRequestPasswordUpdateTest extends MailWithLinkTestCase
{
    public function testSetLogin()
    {
        $mail = $this->getMail();
        $this->assertTrue(false !== strpos($mail->getMessage(), 'RomainNeutron'));
    }

    public function getMail()
    {
        $mail = MailRequestPasswordUpdate::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setLogin('RomainNeutron');

        return $mail;
    }

    public function testThatALogicExceptionIsThrownIfNoLoginProvided()
    {
        $mail = MailRequestPasswordUpdate::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }
}
