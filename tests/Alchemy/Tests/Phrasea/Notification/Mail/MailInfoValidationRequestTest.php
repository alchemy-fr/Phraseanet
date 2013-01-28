<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest;
use Alchemy\Phrasea\Exception\LogicException;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest
 */
class MailInfoValidationRequestTest extends MailWithLinkTestCase
{
    public function testSetTitle()
    {
        $this->assertContainsString('Hello World', $this->getMail()->getSubject());
    }

    public function testShouldThrowALogicExceptionIfNoUserProvided()
    {
        $mail = MailInfoValidationRequest::create(
            $this->getApp(),
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
        } catch (LogicException $e) {

        }
    }

    public function testShouldThrowALogicExceptionIfNoTitleProvided()
    {
        $mail = MailInfoValidationRequest::create(
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

    public function testSetUser()
    {
        $this->assertContainsString('jeanPhil', $this->getMail()->getSubject());
    }

    public function getMail()
    {
        $mail = MailInfoValidationRequest::create(
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

        $mail->setTitle('Hello world');

        return $mail;
    }
}
