<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Session;

class AuthenticatorTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::getUser
     */
    public function testGetUser()
    {
        $app = $this->loadApp();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $this->assertNull($authenticator->getUser());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::setUser
     */
    public function testSetUser()
    {
        $app = $this->loadApp();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $user = $this->createUserMock();

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $authenticator->setUser($user);
        $this->assertEquals($user, $authenticator->getUser());
        $authenticator->setUser(null);
        $this->assertNull($authenticator->getUser());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::openAccount
     */
    public function testOpenAccount()
    {
        $app = $this->loadApp();
        $capturedSession = null;

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $user = $this->createUserMock();
        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnvalue(self::$DI['user']->getId()));

        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->once())
            ->method('get_granted_sbas')
            ->will($this->returnValue([]));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        $app['acl'] = $aclProvider;

        $em->expects($this->at(0))
            ->method('persist')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\Session'))
            ->will($this->returnCallback(function ($session) use (&$capturedSession) {
                $capturedSession = $session;
            }));
        $em->expects($this->at(1))
            ->method('flush');

        $repo = $this->createEntityRepositoryMock();
        $repo->expects($this->once())
            ->method('find')
            ->with($session->getId())
            ->will($this->returnValue($session));
        $repoUsers = $this->createEntityRepositoryMock();
        $repoUsers->expects($this->once())
            ->method('find')
            ->with($user->getId())
            ->will($this->returnValue($session));

        $app['repo.sessions'] = $repo;
        $app['repo.users'] = $repoUsers;

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $phsession = $authenticator->openAccount($user);

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\Session', $phsession);
        $this->assertEquals($capturedSession, $phsession);
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::refreshAccount
     */
    public function testRefreshAccount()
    {
        $app = $this->loadApp();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $SFsession = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $sessionId = 4224242;

        $session = new Session();
        $session->setUser($user);

        $ref = new \ReflectionObject($session);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($session, $sessionId);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->exactly(2))
            ->method('find')
            ->with($session->getId())
            ->will($this->returnValue($session));
        $repoUsers = $this->createEntityRepositoryMock();
        $repoUsers->expects($this->once())
            ->method('find')
            ->with($user->getId())
            ->will($this->returnValue($session));

        $app['repo.sessions'] = $repo;
        $app['repo.users'] = $repoUsers;

        $authenticator = new Authenticator($app, $browser, $SFsession, $em);
        $this->assertEquals($session, $authenticator->refreshAccount($session));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::refreshAccount
     */
    public function testRefreshAccountWithWrongSessionShouldThrowException()
    {
        $app = $this->loadApp();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $SFsession = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $sessionId = 4224242;

        $session = new Session();
        $session->setUser($user);

        $ref = new \ReflectionObject($session);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($session, $sessionId);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('find')
            ->with($session->getId())
            ->will($this->returnValue(null));

        $app['repo.sessions'] = $repo;

        $authenticator = new Authenticator($app, $browser, $SFsession, $em);
        try {
            $authenticator->refreshAccount($session);
            $this->fail('Should have raised an exception');
        } catch (RuntimeException $e) {
            $this->assertEquals('Unable to refresh the session, it does not exist anymore', $e->getMessage());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::closeAccount
     */
    public function testCloseAccount()
    {
        $app = self::$DI['app'];
        $user = self::$DI['user'];

        $authenticator = new Authenticator($app, $app['browser'], $app['session'], $app['EM']);
        $authenticator->openAccount($user);
        $this->assertNotNull($authenticator->getUser());
        $authenticator->closeAccount();
        $this->assertNull($authenticator->getUser());
    }

    public function testCloseAccountWhenNoSessionThrowsAnException()
    {
        $app = self::$DI['app'];

        $authenticator = new Authenticator($app, $app['browser'], $app['session'], $app['EM']);
        $this->setExpectedException('Alchemy\Phrasea\Exception\RuntimeException', 'No session to close.');
        $authenticator->closeAccount();
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::isAuthenticated
     */
    public function testIsAuthenticated()
    {
        $app = $this->loadApp();

        $sessionEntity = new Session();
        $sessionEntity->setUser(self::$DI['user']);
        $sessionEntity->setUserAgent('');

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $app['repo.sessions'] = $this->createEntityRepositoryMock();
        $app['repo.sessions']->expects($this->any())
            ->method('find')
            ->with(1)
            ->will($this->returnValue($sessionEntity));

        $userRepository = $this->getMockBuilder('Alchemy\Phrasea\Model\Repositories\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->once())->method('find')->with(
            $this->equalTo(self::$DI['user']->getId())
        )->will($this->returnValue(self::$DI['user']));

        $app['manipulator.user'] = $this
            ->getMockBuilder('Alchemy\Phrasea\Model\Manipulator\UserManipulator')
            ->disableOriginalConstructor()
            ->getMock();

        $app['repo.users'] = $userRepository;

        $session->set('usr_id', self::$DI['user']->getId());
        $session->set('session_id', 1);

        $authenticator = new Authenticator($app, $browser, $session,  $app['EM']);
        $this->assertTrue($authenticator->isAuthenticated());
        $this->assertEquals(self::$DI['user'], $authenticator->getUser());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::isAuthenticated
     */
    public function testIsNotAuthenticated()
    {
        $app = $this->loadApp();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->createEntityManagerMock();

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $this->assertFalse($authenticator->isAuthenticated());
    }

    private function getSessionMock()
    {
        return new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage());

        return $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\SessionInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getBrowserMock()
    {
        return $this->getMockBuilder('Browser')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
