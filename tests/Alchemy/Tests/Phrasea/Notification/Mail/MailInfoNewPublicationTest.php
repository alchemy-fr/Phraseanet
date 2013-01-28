<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication
 */
class MailInfoNewPublicationTest extends MailWithLinkTestCase
{

    public function testSetTitle()
    {
        $this->assertContainsString('joli titre', $this->getMail()->getSubject());
        $this->assertContainsString('joli titre', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoTitleProvided()
    {
        $mail = MailInfoNewPublication::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $mail->setAuthor('bel author');

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testShouldThrowALogicExceptionIfNoAuthorProvided()
    {
        $mail = MailInfoNewPublication::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $mail->setTitle('joli titre');

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testSetAuthor()
    {
        $this->assertContainsString('bel author', $this->getMail()->getMessage());
    }

    public function getMail()
    {
        $mail = MailInfoNewPublication::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setTitle('joli titre');
        $mail->setAuthor('bel author');

        return $mail;
    }
}
