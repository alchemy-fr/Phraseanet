<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailInfoValidationRequestTest extends MailWithLinkTestCase
{
    public function testSetTitle()
    {
        $this->assertEquals('Validation request from %user% for \'%title%\'', $this->getMail()->getSubject());
    }

    public function testShouldThrowALogicExceptionIfNoUserProvided()
    {
        $mail = MailInfoValidationRequest::create(
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
        $mail = MailInfoValidationRequest::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $user = $this->createUserMock();

        $user
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
        $this->assertEquals('Validation request from %user% for \'%title%\'', $this->getMail()->getSubject());
    }

    public function getMail()
    {
        $mail = MailInfoValidationRequest::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        $user = $this->createUserMock();

        $user
            ->method('getDisplayName')
            ->will($this->returnValue('JeanPhil'));

        $mail->setUser($user);

        $mail->setTitle('Hello world');

        return $mail;
    }
}
