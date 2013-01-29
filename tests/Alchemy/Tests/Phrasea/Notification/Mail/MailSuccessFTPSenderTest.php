<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailSuccessFTPSender;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessFTPSender
 */
class MailSuccessFTPSenderTest extends MailTestCase
{
    public function testSetServer()
    {
        $this->assertTrue(false !== stripos($this->getMail()->getSubject(), 'ftp://example.com'));
    }

    public function testThatALgicExceptionIsThrownIfNoServerSet()
    {
        $mail = MailSuccessFTPSender::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        }catch(LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailSuccessFTPSender::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
        $mail->setServer('ftp://example.com');

        return $mail;
    }
}
