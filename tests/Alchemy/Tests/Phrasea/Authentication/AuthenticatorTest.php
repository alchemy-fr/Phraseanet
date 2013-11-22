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

        $authenticator = new Authenticator(self::$DI['app'], $browser, $session, $em);
        $this->assertNull($authenticator->getUser());
    }
    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::getUser
     */
    public function testGetUserWhenAuthenticated()
    {
        $app = $this->loadApp();

        $user = self::$DI['user'];

        self::$DI['app']['browser'] = $browser = $this->getBrowserMock();
        self::$DI['app']['session'] = $session = $this->getSessionMock();

        $sessionEntity = new Session();
        $sessionEntity->setUser($user);
        $sessionEntity->setUserAgent('');
        self::$DI['app']['EM']->persist($sessionEntity);
        self::$DI['app']['EM']->flush();

        $session->set('usr_id', $user->getId());
        $session->set('session_id', $sessionEntity->getId());

        $authenticator = new Authenticator(self::$DI['app'], $browser, $session, self::$DI['app']['EM']);
        $this->assertEquals($user, $authenticator->getUser());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::setUser
     */
    public function testSetUser()
    {
        $app = $this->loadApp();

        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new Authenticator(self::$DI['app'], $browser, $session, $em);
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

        self::$DI['app']['browser'] = $browser = $this->getBrowserMock();
        self::$DI['app']['session'] = $session = $this->getSessionMock();
        self::$DI['app']['EM'] = $em = $this->getEntityManagerMock();

        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();
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

        self::$DI['app']['acl'] = $aclProvider;

        $em->expects($this->at(0))
            ->method('persist')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\Session'))
            ->will($this->returnCallback(function ($session) use (&$capturedSession) {
                $capturedSession = $session;
            }));
        $em->expects($this->at(1))
            ->method('flush');

        $authenticator = new Authenticator(self::$DI['app'], $browser, $session, $em);
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

        self::$DI['app']['browser'] = $browser = $this->getBrowserMock();
        self::$DI['app']['session'] = $SFsession = $this->getSessionMock();
        self::$DI['app']['EM'] = $em = $this->getEntityManagerMock();

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
            ->method('findOneBy')
            ->with($this->equalTo(['id' => $session->getId()]))
            ->will($this->returnValue($session));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Phraseanet:Session'))
            ->will($this->returnValue($repo));

        $authenticator = new Authenticator(self::$DI['app'], $browser, $SFsession, $em);
        $this->assertEquals($session, $authenticator->refreshAccount($session));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::refreshAccount
     */
    public function testRefreshAccountWithWrongSessionShouldThrowException()
    {
        $app = $this->loadApp();

        $user = self::$DI['user'];

        self::$DI['app']['browser'] = $browser = $this->getBrowserMock();
        self::$DI['app']['session'] = $SFsession = $this->getSessionMock();
        self::$DI['app']['EM'] = $em = $this->getEntityManagerMock();

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
            ->method('findOneBy')
            ->with($this->equalTo(['id' => $session->getId()]))
            ->will($this->returnValue(null));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Phraseanet:Session'))
            ->will($this->returnValue($repo));

        $authenticator = new Authenticator(self::$DI['app'], $browser, $SFsession, $em);
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

        $user = self::$DI['user'];

        self::$DI['app']['browser'] = $browser = $this->getBrowserMock();
        self::$DI['app']['session'] = $session = $this->getSessionMock();

        $sessionEntity = new Session();
        $sessionEntity->setUser($user);
        $sessionEntity->setUserAgent('');
        self::$DI['app']['EM']->persist($sessionEntity);
        self::$DI['app']['EM']->flush();

        $session->set('usr_id', $user->getId());
        $session->set('session_id', $sessionEntity->getId());

        $authenticator = new Authenticator(self::$DI['app'], $browser, $session, self::$DI['app']['EM']);
        $this->assertTrue($authenticator->isAuthenticated());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::isAuthenticated
     */
    public function testIsNotAuthenticated()
    {
        $app = $this->loadApp();

        $authenticator = new Authenticator(self::$DI['app'], $browser, $session, $em);
        $this->assertFalse($authenticator->isAuthenticated());
    }

    private function getEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getRegistryMock()
    {
        return $this->getMockBuilder('registryInterface')
            ->disableOriginalConstructor()
            ->getMock();
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
