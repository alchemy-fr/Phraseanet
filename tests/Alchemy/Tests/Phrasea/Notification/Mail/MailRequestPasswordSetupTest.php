<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup;
use Alchemy\Phrasea\Exception\LogicException;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup
 */
class MailRequestPasswordSetupTest extends MailWithLinkTestCase
{
    public function testSetLogin()
    {
        $mail = $this->getMail();
        $this->assertEquals("Your account with the login %login% as been created\nYou now have to set up your pasword", $mail->getMessage());
    }

    public function testThatALogicExceptionIsThrownIfNoLoginProvided()
    {
        $mail = MailRequestPasswordSetup::create(
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

    public function getMail()
    {
        $mail = MailRequestPasswordSetup::create(
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
}
