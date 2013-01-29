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
        $this->assertContainsString('42', $this->getMail()->getMessage());
    }

    public function testSetDeliverer()
    {
        $this->assertContainsString('JeanPhil', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoQuantityProvided()
    {
        $mail = MailInfoOrderCancelled::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_display_name')
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

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_display_name')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);
        $mail->setQuantity(42);

        return $mail;
    }
}
