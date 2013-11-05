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
        $this->assertEquals('push::mail:: Reception de votre commande %title%', $this->getMail()->getSubject());
    }

    public function testSetDeliverer()
    {
        $this->assertEquals('%user% vous a delivre votre commande, consultez la en ligne a l\'adresse suivante', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoDelivererProvided()
    {
        $mail = MailInfoOrderDelivered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
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

    public function testShouldThrowALogicExceptionIfNoBasketProvided()
    {
        $mail = MailInfoOrderDelivered::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);

        try {
            $mail->getSubject();
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

        $basket = $this->getMock('Alchemy\Phrasea\Model\Entities\Basket');
        $basket->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Hello basket'));
        $basket->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(42));

        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);
        $mail->setBasket($basket);
        $mail->setButtonUrl('http://example.com/path/to/basket');

        return $mail;
    }
}
