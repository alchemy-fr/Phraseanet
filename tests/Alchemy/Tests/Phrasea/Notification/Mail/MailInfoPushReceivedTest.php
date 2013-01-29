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

        $collection = $this->getMock('Doctrine\Common\Collections\ArrayCollection');
        $collection->expects($this->any())
            ->method('count')
            ->will($this->returnValue(5));

        $basket = $this->getMock('Entities\Basket');
        $basket->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Hello basket'));
        $basket->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue($collection));

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_display_name')
            ->will($this->returnValue('JeanPhil'));

        $mail->setPusher($user);
        $mail->setBasket($basket);

        return $mail;
    }
}
