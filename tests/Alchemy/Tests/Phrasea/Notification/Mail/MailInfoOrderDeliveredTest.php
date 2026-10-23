<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
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
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $basket = $this->createMock('Alchemy\Phrasea\Model\Entities\Basket');
        $basket
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
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->createUserMock();

        $user
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
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $basket = $this->createMock('Alchemy\Phrasea\Model\Entities\Basket');
        $basket
            ->method('getName')
            ->will($this->returnValue('Hello basket'));
        $basket
            ->method('getId')
            ->will($this->returnValue(42));

        $user = $this->createUserMock();

        $user
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setDeliverer($user);
        $mail->setBasket($basket);
        $mail->setButtonUrl('http://example.com/path/to/basket');

        return $mail;
    }
}
