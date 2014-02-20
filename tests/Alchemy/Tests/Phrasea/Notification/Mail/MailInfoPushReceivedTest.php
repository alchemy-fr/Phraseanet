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

        $this->assertEquals('Reception of %basket_name%', $mail->getSubject());
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

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getFirstName')
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

        $this->assertEquals("You just received a push containing %quantity% documents from %user%\nLorem ipsum dolor", $mail->getMessage());
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

        $basket = $this->getMock('Alchemy\Phrasea\Model\Entities\Basket');
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

        $this->assertEquals("You just received a push containing %quantity% documents from %user%\nLorem ipsum dolor", $mail->getMessage());
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

        $basket = $this->getMock('Alchemy\Phrasea\Model\Entities\Basket');
        $basket->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Hello basket'));
        $basket->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue($collection));

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setPusher($user);
        $mail->setBasket($basket);

        return $mail;
    }
}
