<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled
 */
class MailInfoOrderCancelledTest extends MailTestCase
{
    public function testSetQuantity()
    {
        $this->assertEquals('%user% a refuse %quantity% elements de votre commande', $this->getMail()->getMessage());
    }

    public function testSetDeliverer()
    {
        $this->assertEquals('%user% a refuse %quantity% elements de votre commande', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoQuantityProvided()
    {
        $mail = MailInfoOrderCancelled::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }
    public function testShouldThrowALogicExceptionIfNoDelivererProvided()
    {
        $mail = MailInfoOrderCancelled::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $mail->setQuantity(42);

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailInfoOrderCancelled::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);
        $mail->setQuantity(42);

        return $mail;
    }
}
