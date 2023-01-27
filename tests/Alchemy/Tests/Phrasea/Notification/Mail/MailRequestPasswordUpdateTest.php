<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Alchemy\Phrasea\Exception\LogicException;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate
 */
class MailRequestPasswordUpdateTest extends MailWithLinkTestCase
{
    public function testSetLogin()
    {
        $mail = $this->getMail();
        $expected = "mail:: Password renewal for login".
            "\n\n <strong>RomainNeutron</strong> \n\n" .
            "login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien";
        $this->assertEquals($expected, $mail->getMessage());
    }

    public function getMail()
    {
        $mail = MailRequestPasswordUpdate::create(
            $this->getApplication(),
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
            $this->getApplication(),
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
