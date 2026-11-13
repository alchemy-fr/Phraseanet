<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Session as SessionEntity;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class AuthenticatorTest extends \PhraseanetTestCase
{
    public function testGetUser()
    {
        $app = $this->loadApp();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $this->assertNull($authenticator->getUser());
    }

    public function testSetUser()
    {
        $app = $this->loadApp();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $user = $this->createUserMock();

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $authenticator->setUser($user);
        $this->assertEquals($user, $authenticator->getUser());
        $authenticator->setUser(null);
        $this->assertNull($authenticator->getUser());
    }

    public function testOpenAccount()
    {
        $app = $this->loadApp();
        /** @var SessionEntity $capturedSession */
        $capturedSession = null;

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $user = $this->createUserMock();
        $user
            ->method('getId')
            ->will($this->returnvalue(self::$DI['user']->getId()));

        $acl = $this->createMock('ACL');
        $acl->expects($this->once())
            ->method('get_granted_sbas')
            ->will($this->returnValue([]));

        $aclProvider = $this->createMock('Alchemy\Phrasea\Authentication\ACLProvider');
        $aclProvider
            ->method('get')
            ->will($this->returnValue($acl));

        $app['acl'] = $aclProvider;

        $em->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedSession) {
                if ($entity instanceof SessionEntity) {
                    $capturedSession = $entity;
                    $capturedSession->setCreated(new \Datetime());
                }
            });
        $em->expects($this->exactly(2))
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

    public function testRefreshAccount()
    {
        $app = $this->loadApp();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $SFsession = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $sessionId = 4224242;

        $session = new SessionEntity();
        $session->setUser($user)->setCreated(new \DateTime());

        $ref = new \ReflectionObject($session);
        $prop = $ref->getProperty('id');
        $prop->setValue($session, $sessionId);

        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

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

    public function testRefreshAccountWithWrongSessionShouldThrowException()
    {
        $app = $this->loadApp();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $SFsession = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $sessionId = 4224242;

        $session = new SessionEntity();
        $session->setUser($user);

        $ref = new \ReflectionObject($session);
        $prop = $ref->getProperty('id');
        $prop->setValue($session, $sessionId);

        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

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

    public function testCloseAccount()
    {
        $app = $this->getApplication();
        $user = self::$DI['user'];

        $authenticator = new Authenticator($app, $app['browser'], $app['session'], $app['orm.em']);
        $authenticator->openAccount($user);
        $this->assertNotNull($authenticator->getUser());
        $authenticator->closeAccount();
        $this->assertNull($authenticator->getUser());
    }

//    public function testCloseAccountWhenNoSessionThrowsAnException()
    //    {
    //        $app = $this->getApplication();
    //
    //        $authenticator = new Authenticator($app, $app['browser'], $app['session'], $app['orm.em']);
    //        $this->setExpectedException('Alchemy\Phrasea\Exception\RuntimeException', 'No session to close.');
    //        $authenticator->closeAccount();
    //    }
    public function testIsAuthenticated()
    {
        $app = $this->loadApp();

        $sessionEntity = new SessionEntity();
        $sessionEntity->setUser(self::$DI['user']);
        $sessionEntity->setUserAgent('');

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $app['repo.sessions'] = $this->createEntityRepositoryMock();
        $app['repo.sessions']
            ->method('find')
            ->with(1)
            ->will($this->returnValue($sessionEntity));

        $userRepository = $this->createMock('Alchemy\Phrasea\Model\Repositories\UserRepository');
        $userRepository->expects($this->once())->method('find')->with(
            $this->equalTo(self::$DI['user']->getId())
        )->will($this->returnValue(self::$DI['user']));

        $app['manipulator.user'] = $this->createStub('Alchemy\Phrasea\Model\Manipulator\UserManipulator');

        $app['repo.users'] = $userRepository;

        $session->set('usr_id', self::$DI['user']->getId());
        $session->set('session_id', 1);

        $authenticator = new Authenticator($app, $browser, $session,  $app['orm.em']);
        $this->assertTrue($authenticator->isAuthenticated());
        $this->assertEquals(self::$DI['user'], $authenticator->getUser());
    }

    public function testIsNotAuthenticated()
    {
        $app = $this->loadApp();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['orm.em'] = $em = $this->createEntityManagerMock();

        $authenticator = new Authenticator($app, $browser, $session, $em);
        $this->assertFalse($authenticator->isAuthenticated());
    }

    private function getSessionMock()
    {
        $session = new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage());
        return $session;

        return $this->createMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
    }

    private function getBrowserMock()
    {
        return $this->createMock('Browser');
    }
}
