<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone;
use Alchemy\Phrasea\Exception\LogicException;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone
 */
class MailInfoValidationDoneTest extends MailWithLinkTestCase
{
    /**
     * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone::setTitle
     */
    public function testSetTitle()
    {
        $this->assertEquals('push::mail:: Rapport de validation de %user% pour %title%', $this->getMail()->getSubject());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone::setUser
     */
    public function testSetUser()
    {
        $this->assertEquals('push::mail:: Rapport de validation de %user% pour %title%', $this->getMail()->getSubject());
        $this->assertEquals('%user% has just sent its validation report, you can now see it', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoTitleProvided()
    {
        $mail = MailInfoValidationDone::create(
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
            ->method('get_display_name')
            ->will($this->returnValue('JeanPhil'));

        $mail->setUser($user);

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function testShouldThrowALogicExceptionIfNoUserProvided()
    {
        $mail = MailInfoValidationDone::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $mail->setTitle('Hulk hogan');

        try {
            $mail->getSubject();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailInfoValidationDone::create(
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
            ->method('get_display_name')
            ->will($this->returnValue('JeanPhil'));

        $mail->setTitle('Hulk hogan');
        $mail->setUser($user);

        return $mail;
    }
}
