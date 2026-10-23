<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Model\Entities\User;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class NativeAuthenticationTest extends \PhraseanetTestCase
{
    public function testAuthenticationSpecialUser()
    {
        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();

        $specialUser = $this->createUserMock();
        $specialUser->method('isSpecial')->will($this->returnValue(true));

        $manipulator = $this->getUserManipulatorMock();
        $entityRepo = $this->getEntityRepositoryMock($specialUser);

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);
        $this->assertNull($auth->getUsrId('a_login', 'a_password', $request));
    }

    public function testNotFoundIsNotValid()
    {
        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();
        $manipulator = $this->getUserManipulatorMock();
        $entityRepo = $this->getEntityRepositoryMock(null);

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);
        $this->assertNull($auth->getUsrId('a_login', 'a_password', $request));
    }

    public function testLockAccountThrowsAnException()
    {
        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();

        $mailLockedUser = $this->createUserMock();
        $mailLockedUser->method('isMailLocked')->will($this->returnValue(true));

        $manipulator = $this->getUserManipulatorMock();
        $entityRepo = $this->getEntityRepositoryMock($mailLockedUser);

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);

        try {
            $auth->getUsrId('a_login', 'a_password', $request);
            $this->fail('Should have raised an exception');
        } catch (AccountLockedException $e) {

        }
    }

    public function testGetUsrIdWithCorrectCredentials()
    {
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $userId = 42;

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();

        $user = $this->createUserMock();

        $user->method('getId')->will($this->returnValue($userId));
        $user->method('isSpecial')->will($this->returnValue(false));
        $user->method('isMailLocked')->will($this->returnValue(false));
        $user->method('isSaltedPassword')->will($this->returnValue(true));
        $user->method('getPassword')->will($this->returnValue($encoded));
        $user->method('getNonce')->will($this->returnValue($nonce));

        $manipulator = $this->getUserManipulatorMock();
        $entityRepo = $this->getEntityRepositoryMock($user);

        $oldEncoder->expects($this->never())
            ->method('isPasswordValid');

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(true));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);

        $this->assertEquals($userId, $auth->getUsrId('a_login', $password, $request));
    }

    public function testIsNotValidWithIncorrectCredentials()
    {
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $userId = 42;

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();

        $user = $this->createUserMock();

        $user->method('getId')->will($this->returnValue($userId));
        $user->method('isSpecial')->will($this->returnValue(false));
        $user->method('isMailLocked')->will($this->returnValue(false));
        $user->method('isSaltedPassword')->will($this->returnValue(true));
        $user->method('getPassword')->will($this->returnValue($encoded));
        $user->method('getNonce')->will($this->returnValue($nonce));

        $manipulator = $this->getUserManipulatorMock();
        $entityRepo = $this->getEntityRepositoryMock($user);

        $oldEncoder->expects($this->never())
            ->method('isPasswordValid');

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(false));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);

        $this->assertEquals(false, $auth->getUsrId('a_login', $password, $request));
    }

    public function testIsNotValidWithIncorrectOldCredentials()
    {
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $userId = 42;

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();

        $user = $this->createUserMock();

        $user->method('getId')->will($this->returnValue($userId));
        $user->method('isSpecial')->will($this->returnValue(false));
        $user->method('isMailLocked')->will($this->returnValue(false));
        $user->method('isSaltedPassword')->will($this->returnValue(false));
        $user->method('getPassword')->will($this->returnValue($encoded));
        $user->method('getNonce')->will($this->returnValue($nonce));

        $manipulator = $this->getUserManipulatorMock($user);
        $entityRepo = $this->getEntityRepositoryMock($user);

        $oldEncoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(false));

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(false));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);

        $this->assertEquals(false, $auth->getUsrId('a_login', $password, $request));
    }

    public function testGetUsrIdWithCorrectOldCredentials()
    {
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $userId = 42;

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $request = $this->getRequestMock();

        $user = $this->createUserMock();

        $user->method('getId')->will($this->returnValue($userId));
        $user->method('isSpecial')->will($this->returnValue(false));
        $user->method('isMailLocked')->will($this->returnValue(false));
        $user->method('isSaltedPassword')->will($this->returnValue(false));
        $user->method('getPassword')->will($this->returnValue($encoded));
        $user->method('getNonce')->will($this->returnValue($nonce));

        $manipulator = $this->getUserManipulatorMock();
        $entityRepo = $this->getEntityRepositoryMock($user);

        $manipulator->expects($this->once())->method('setPassword')->with($this->equalTo($user), $this->equalTo($password));

        $oldEncoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(true));

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->will($this->returnCallback(function ($encoded, $pass, $nonce) use (&$catchTestPassword) {
                $catchTestPassword = [
                    'encoded' => $encoded,
                    'pass' => $pass,
                    'nonce' => $nonce,
                ];

                return true;
            }));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $manipulator, $entityRepo);
        $this->assertEquals($userId, $auth->getUsrId('a_login', $password, $request));
    }

    private function getEncoderMock()
    {
        return $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder');
    }

    private function getOldEncoderMock()
    {
        return $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder');
    }

    private function getRequestMock()
    {
        return $this->createMock('Symfony\Component\HttpFoundation\Request');
    }

    private function getUserManipulatorMock()
    {
        $manipulator = $this->createMock('Alchemy\Phrasea\Model\Manipulator\UserManipulator');

        return $manipulator;
    }

    private function getEntityRepositoryMock(?User $user = null)
    {
        $repoMock = $this->createMock('Alchemy\Phrasea\Model\Repositories\UserRepository');
        $repoMock->method('findRealUserByLogin')->will($this->returnValue($user));

        return $repoMock;
    }
}
