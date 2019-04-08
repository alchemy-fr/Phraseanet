<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;
use Alchemy\Phrasea\Notification\Mail\MailRequestAccountDelete;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailRequestAccountDelete
 */
class MailRequestAccountDeleteTest extends MailWithLinkTestCase
{
    /**
     * @covers Alchemy\Phrasea\Notification\Mail\MailRequestAccountDelete::setUserOwner
     */
    public function testSetUserOwner()
    {
        $this->assertEquals('Email:deletion:request:message Hello %civility% %firstName% %lastName%.
            We have received an account deletion request for your account on %urlInstance%, please confirm this deletion by clicking on the link below.
            If you are not at the origin of this request, please change your password as soon as possible %resetPassword%
            Link is valid for one hour.', $this->getMail()->getMessage());
    }

    public function testShouldThrowALogicExceptionIfNoUserProvided()
    {
        $mail = MailRequestAccountDelete::create(
            $this->getApplication(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage(),
            $this->getUrl(),
            $this->getExpiration()
        );

        try {
            $mail->getMessage();
            $this->fail('Should have raised an exception');
        } catch (LogicException $e) {

        }
    }

    public function getMail()
    {
        $mail = MailRequestAccountDelete::create(
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

        $mail->setUserOwner($user);

        return $mail;
    }
}
