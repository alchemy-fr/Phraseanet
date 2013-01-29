<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered;

/**
 * @covers Alchemy\Phrasea\Notification\Mail\MailInfoUserRegistered
 */
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
            $this->getApp(),
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
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );

        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_firstname')
            ->will($this->returnValue('JeanFirstName'));

        $user->expects($this->any())
            ->method('get_lastname')
            ->will($this->returnValue('JeanLastName'));

        $user->expects($this->any())
            ->method('get_job')
            ->will($this->returnValue('JeanJob'));

        $user->expects($this->any())
            ->method('get_company')
            ->will($this->returnValue('JeanCompany'));

        $mail->setRegisteredUser($user);

        return $mail;
    }
}
