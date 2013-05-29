<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Phrasea\NativeAuthentication;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;

class NativeAuthenticationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideReservedUsernames
     */
    public function testReservedAreValid($username)
    {
        $password = 'popo42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $conn = $this->getMock('connection_interface');
        $request = $this->getRequestMock();

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);
        $this->assertNull($auth->getUsrId($username, $password, $request));
    }

    public function provideReservedUsernames()
    {
        return array(
            array('autoregister'),
            array('invite'),
        );
    }

    public function testNotFoundIsNotValid()
    {
        $username = 'romainneutron';
        $password = 'popo42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $conn = $this->getConnectionMock($username, null);
        $request = $this->getRequestMock();

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);
        $this->assertNull($auth->getUsrId($username, $password, $request));
    }

    public function testLockAccountThrowsAnException()
    {
        $username = 'romainneutron';
        $password = 'popo42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $conn = $this->getConnectionMock($username, array(
            'nonce' => 'dfqsdgqsd',
            'salted_password' => '1',
            'mail_locked' => '1',
            'usr_id' => '1',
            'usr_password' => 'qsdfsqdfqsd',
        ));
        $request = $this->getRequestMock();

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);

        try {
            $auth->getUsrId($username, $password, $request);
            $this->fail('Should have raised an exception');
        } catch (AccountLockedException $e) {

        }
    }

    public function testGetUsrIdWithCorrectCredentials()
    {
        $username = 'romainneutron';
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $usr_id = '42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $conn = $this->getConnectionMock($username, array(
            'nonce' => $nonce,
            'salted_password' => '1',
            'mail_locked' => '0',
            'usr_id' => $usr_id,
            'usr_password' => $encoded,
        ));
        $request = $this->getRequestMock();

        $oldEncoder->expects($this->never())
            ->method('isPasswordValid');

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(true));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);

        $this->assertEquals($usr_id, $auth->getUsrId($username, $password, $request));
    }

    public function testIsNotValidWithIncorrectCredentials()
    {
        $username = 'romainneutron';
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $usr_id = '42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $conn = $this->getConnectionMock($username, array(
            'nonce' => $nonce,
            'salted_password' => '1',
            'mail_locked' => '0',
            'usr_id' => $usr_id,
            'usr_password' => $encoded,
        ));
        $request = $this->getRequestMock();

        $oldEncoder->expects($this->never())
            ->method('isPasswordValid');

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(false));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);

        $this->assertEquals(false, $auth->getUsrId($username, $password, $request));
    }

    public function testIsNotValidWithIncorrectOldCredentials()
    {
        $username = 'romainneutron';
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $usr_id = '42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();
        $conn = $this->getConnectionMock($username, array(
            'nonce' => $nonce,
            'salted_password' => '0',
            'mail_locked' => '0',
            'usr_id' => $usr_id,
            'usr_password' => $encoded,
        ));
        $request = $this->getRequestMock();

        $oldEncoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(false));

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(false));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);

        $this->assertEquals(false, $auth->getUsrId($username, $password, $request));
    }

    public function testGetUsrIdWithCorrectOldCredentials()
    {
        $username = 'romainneutron';
        $password = 'popo42';
        $encoded = 'qsdfsqdfqsd';
        $nonce = 'dfqsdgqsd';
        $usr_id = '42';

        $encoder = $this->getEncoderMock();
        $oldEncoder = $this->getOldEncoderMock();

        $conn = $this->getMock('connection_interface');

        $statement = $this->getMock('PDOStatement');
        $statement
            ->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(array(':login' => $username)));
        $statement->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(\PDO::FETCH_ASSOC))
            ->will($this->returnValue(array(
            'nonce' => $nonce,
            'salted_password' => '0',
            'mail_locked' => '0',
            'usr_id' => $usr_id,
            'usr_password' => $encoded,
        )));

        $catchParameters = $catchTestPassword = null;

        $statement2 = $this->getMock('PDOStatement');
        $statement2
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnCallback(function ($parameters) use (&$catchParameters) {
                $catchParameters = $parameters;
            }));

        $conn->expects($this->at(0))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($statement));

        $conn->expects($this->at(1))
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($statement2));

        $request = $this->getRequestMock();

        $oldEncoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->equalTo($encoded), $this->equalTo($password), $this->equalTo($nonce))
            ->will($this->returnValue(true));

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->will($this->returnCallback(function ($encoded, $pass, $nonce) use (&$catchTestPassword) {
                $catchTestPassword = array(
                    'encoded' => $encoded,
                    'pass' => $pass,
                    'nonce' => $nonce,
                );

                return true;
            }));

        $auth = new NativeAuthentication($encoder, $oldEncoder, $conn);
        $this->assertEquals($usr_id, $auth->getUsrId($username, $password, $request));

        $this->assertEquals($catchParameters[':password'], $catchTestPassword['encoded']);
        $this->assertEquals($password, $catchTestPassword['pass']);
        $this->assertEquals($catchParameters[':nonce'], $catchTestPassword['nonce']);
        $this->assertEquals($usr_id, $catchParameters[':usr_id']);
    }

    private function getConnectionMock($username, $row = null)
    {
        $conn = $this->getMock('connection_interface');

        $statement = $this->getMock('PDOStatement');

        $statement
            ->expects($this->once())
            ->method('execute')
            ->with($this->equalTo(array(':login' => $username)));

        $statement->expects($this->once())
            ->method('fetch')
            ->with($this->equalTo(\PDO::FETCH_ASSOC))
            ->will($this->returnValue($row));

        $conn->expects($this->once())
            ->method('prepare')
            ->with($this->isType('string'))
            ->will($this->returnValue($statement));

        return $conn;
    }

    private function getEncoderMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getOldEncoderMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\Phrasea\OldPasswordEncoder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getFailureManagerMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\Phrasea\FailureManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getRequestMock()
    {
        return $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
