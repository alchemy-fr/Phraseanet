<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered
 */
class MailInfoOrderDeliveredTest extends MailTestCase
{
    public function testSetBasket()
    {
        $this->assertContainsString('Hello basket', $this->getMail()->getSubject());
    }

    public function testSetDeliverer()
    {
        $this->assertContainsString('JeanPhil', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoDelivererProvided()
    {
        $mail = MailInfoOrderDelivered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $basket = $this->getMock('Entities\Basket');
        $basket->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Hello basket'));

        $mail->setBasket($basket);

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testShouldThrowALogicExceptionIfNoBasketProvided()
    {
        $mail = MailInfoOrderDelivered::create(
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
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }

        try {
            $mail->getButtonURL();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailInfoOrderDelivered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $basket = $this->getMock('Entities\Basket');
        $basket->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Hello basket'));

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_display_name')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);
        $mail->setBasket($basket);

        return $mail;
    }
}
