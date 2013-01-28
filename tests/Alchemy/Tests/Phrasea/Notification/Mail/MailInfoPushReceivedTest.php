<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived
 */
class MailInfoPushReceivedTest extends MailWithLinkTestCase
{
    public function testSetBasket()
    {
        $mail = $this->getMail();

        $this->assertContainsString('Hello basket', $mail->getSubject());
    }

    public function testShouldThrowLogicExceptionsIfBasketNotSet()
    {
        $mail = MailInfoPushReceived::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_firstname')
            ->will($this->returnValue('JeanFirstName'));

        $mail->setPusher($user);
        $mail->setQuantity(5);

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testSetPusher()
    {
        $mail = $this->getMail();

        $this->assertContainsString('JeanPhil', $mail->getMessage());
    }

    public function testShouldThrowLogicExceptionsIfPusherNotSet()
    {
        $mail = MailInfoPushReceived::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $basket = $this->getMock('Entities\Basket');
        $basket->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Hello basket'));

        $mail->setQuantity(5);
        $mail->setBasket($basket);

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testSetQuantity()
    {
        $mail = $this->getMail();

        $this->assertContainsString('5', $mail->getMessage());
    }

    public function testShouldThrowLogicExceptionsIfQuantityNotSet()
    {
        $mail = MailInfoPushReceived::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
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

        $mail->setPusher($user);
        $mail->setBasket($basket);

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailInfoPushReceived::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
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

        $mail->setPusher($user);
        $mail->setBasket($basket);
        $mail->setQuantity(5);

        return $mail;
    }
}
