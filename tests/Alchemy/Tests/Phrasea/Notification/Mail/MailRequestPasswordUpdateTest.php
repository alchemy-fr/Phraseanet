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
        $this->assertEquals("Password renewal for login \"%login%\" has been requested\nlogin:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien", $mail->getMessage());
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
