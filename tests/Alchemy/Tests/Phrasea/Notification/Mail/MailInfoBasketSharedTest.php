<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoBasketShared;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest
 */
class MailInfoBasketSharedTest extends MailWithLinkTestCase
{
    public function testSetTitle()
    {
        $this->assertEquals('Basket \'%title%\' shared from %user%', $this->getMail()->getSubject());
    }

    public function getMail()
    {
        $mail = MailInfoBasketShared::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setUser($user);

        $mail->setTitle('Hello world');

        return $mail;
    }

    public function testShouldThrowALogicExceptionIfNoUserProvided()
    {
        $mail = MailInfoBasketShared::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setTitle('Hello world');

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        }
        catch (LogicException $e) {
            // no-op
        }
    }

    public function testShouldThrowALogicExceptionIfNoTitleProvided()
    {
        $mail = MailInfoBasketShared::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $user = $this->createUserMock();

        $user->expects($this->any())
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setUser($user);

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        }
        catch (LogicException $e) {
            // no-op
        }
    }

    public function testSetUser()
    {
        $this->assertEquals('Basket \'%title%\' shared from %user%', $this->getMail()->getSubject());
    }
}
