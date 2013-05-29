<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Exception\RuntimeException;

class AuthenticatorTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::getUser
     */
    public function testGetUser()
    {
        $app = new Application();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
        $this->assertNull($authenticator->getUser());
    }
    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::getUser
     */
    public function testGetUserWhenAuthenticated()
    {
        $app = new Application();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $session->set('usr_id', $user->get_id());

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
        $this->assertEquals($user, $authenticator->getUser());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::setUser
     */
    public function testSetUser()
    {
        $app = new Application();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $user = $this->getMockBuilder('\User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
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
        $app = new Application();

        $sessionId = 2442;

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $user = $this->getMockBuilder('\User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('get_id')
            ->will($this->returnvalue(self::$DI['user']->get_id()));

        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->once())
            ->method('get_granted_sbas')
            ->will($this->returnValue(array()));

        $user->expects($this->once())
            ->method('ACL')
            ->will($this->returnValue($acl));

        $em->expects($this->at(0))
            ->method('persist')
            ->with($this->isInstanceOf('Entities\Session'))
            ->will($this->returnCallback(function ($session) use ($sessionId) {
                $ref = new \ReflectionObject($session);
                $prop = $ref->getProperty('id');
                $prop->setAccessible(true);
                $prop->setValue($session, $sessionId);
            }));
        $em->expects($this->at(1))
            ->method('flush');

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
        $phsession = $authenticator->openAccount($user);

        $this->assertInstanceOf('Entities\Session', $phsession);
        $this->assertEquals($sessionId, $session->get('session_id'));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::refreshAccount
     */
    public function testRefreshAccount()
    {
        $app = new Application();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $SFsession = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnValue('random-data' . mt_rand()));

        $usrId = $user->get_id();
        $sessionId = 4224242;

        $session = new \Entities\Session();
        $session->setUsrId($usrId);

        $ref = new \ReflectionObject($session);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($session, $sessionId);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('id' => $session->getId())))
            ->will($this->returnValue($session));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Entities\Session'))
            ->will($this->returnValue($repo));

        $authenticator = new Authenticator($app, $browser, $SFsession, $em, $registry);
        $this->assertEquals($session, $authenticator->refreshAccount($session));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::refreshAccount
     */
    public function testRefreshAccountWithWrongSessionShouldThrowException()
    {
        $app = new Application();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $SFsession = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $app['phraseanet.registry']->expects($this->any())
            ->method('get')
            ->will($this->returnValue('random-data' . mt_rand()));

        $usrId = $user->get_id();
        $sessionId = 4224242;

        $session = new \Entities\Session();
        $session->setUsrId($usrId);

        $ref = new \ReflectionObject($session);
        $prop = $ref->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($session, $sessionId);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(array('id' => $session->getId())))
            ->will($this->returnValue(null));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Entities\Session'))
            ->will($this->returnValue($repo));

        $authenticator = new Authenticator($app, $browser, $SFsession, $em, $registry);
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
        $app = new Application();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $session->set('usr_id', $user->get_id());

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
        $authenticator->closeAccount();
        $this->assertNull($authenticator->getUser());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::isAuthenticated
     */
    public function testIsAuthenticated()
    {
        $app = new Application();

        $user = self::$DI['user'];

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $session->set('usr_id', $user->get_id());

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
        $this->assertTrue($authenticator->isAuthenticated());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Authenticator::isAuthenticated
     */
    public function testIsNotAuthenticated()
    {
        $app = new Application();

        $app['browser'] = $browser = $this->getBrowserMock();
        $app['session'] = $session = $this->getSessionMock();
        $app['EM'] = $em = $this->getEntityManagerMock();
        $app['phraseanet.registry'] = $registry = $this->getRegistryMock();

        $authenticator = new Authenticator($app, $browser, $session, $em, $registry);
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
