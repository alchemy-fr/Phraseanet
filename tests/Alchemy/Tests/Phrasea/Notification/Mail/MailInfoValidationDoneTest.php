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
        $this->assertContainsString('Hulk Hogan', $this->getMail()->getSubject());
    }

    /**
     * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationDone::setUser
     */
    public function testSetUser()
    {
        $this->assertContainsString('JeanPhil', $this->getMail()->getSubject());
        $this->assertContainsString('JeanPhil', $this->getMail()->getMessage());
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
