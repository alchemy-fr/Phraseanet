<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
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
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $user = $this->createUserMock();

        $user
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
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
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

    public function testSetQuantity()
    {
        $mail = $this->getMail();

        $this->assertEquals("You just received a push containing %quantity% documents from %user%\nLorem ipsum dolor", $mail->getMessage());
    }

    public function getMail()
    {
        $mail = MailInfoPushReceived::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $collection = $this->createMock('Doctrine\Common\Collections\ArrayCollection');
        $collection
            ->method('count')
            ->will($this->returnValue(5));

        $basket = $this->createMock('Alchemy\Phrasea\Model\Entities\Basket');
        $basket
            ->method('getName')
            ->will($this->returnValue('Hello basket'));
        $basket
            ->method('getElements')
            ->will($this->returnValue($collection));

        $user = $this->createUserMock();

        $user
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setPusher($user);
        $mail->setBasket($basket);

        return $mail;
    }
}
