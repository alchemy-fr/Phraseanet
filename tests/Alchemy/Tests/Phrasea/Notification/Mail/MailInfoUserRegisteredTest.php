<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MailInfoUserRegisteredTest extends MailTestCase
{
    public function testSetRegisteredUser()
    {
        $mail = $this->getMail();

        $this->assertTrue(false !== stripos($mail->getMessage(), 'JeanFirstName'));
        $this->assertTrue(false !== stripos($mail->getMessage(), 'JeanLastName'));
        $this->assertTrue(false !== stripos($mail->getMessage(), 'JeanJob'));
        $this->assertTrue(false !== stripos($mail->getMessage(), 'JeanCompany'));
    }

    public function testGetMessageWithoutRegisteredUserShouldThrowALogicException()
    {
        $mail = MailInfoUserRegistered::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailInfoUserRegistered::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->createUserMock();

        $user
            ->method('getFirstName')
            ->will($this->returnValue('JeanFirstName'));

        $user
            ->method('getLastName')
            ->will($this->returnValue('JeanLastName'));

        $user
            ->method('getJob')
            ->will($this->returnValue('JeanJob'));

        $user
            ->method('getCompany')
            ->will($this->returnValue('JeanCompany'));

        $mail->setRegisteredUser($user);

        return $mail;
    }
}
