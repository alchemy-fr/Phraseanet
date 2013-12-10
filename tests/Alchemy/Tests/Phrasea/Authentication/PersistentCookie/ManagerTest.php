<?php

namespace Alchemy\Phrasea\Authentication\PersistentCookie;

use Alchemy\Phrasea\Authentication\PersistentCookie\Manager;
use Alchemy\Phrasea\Model\Entities\Session;

class ManagerTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\PersistentCookie\Manager::getSession
     */
    public function testGetSession()
    {
        $encoder = $this->getPasswordEncoderMock();
        $em = $this->getEntityManagerMock();
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $browser->expects($this->once())
            ->method('getBrowser')
            ->will($this->returnValue('Firefox'));

        $browser->expects($this->once())
            ->method('getPlatform')
            ->will($this->returnValue('Linux'));

        $manager = new Manager($encoder, $em, $browser);

        $session = new Session();
        $session->setNonce('prettyN0nce');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue($session));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Alchemy\Phrasea\Model\Entities\Session'))
            ->will($this->returnValue($repo));

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->anything(), 'Firefox_Linux', 'prettyN0nce')
            ->will($this->returnValue(true));

        $this->assertSame($session, $manager->getSession($tokenValue));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\PersistentCookie\Manager::getSession
     */
    public function testGetSessionReturnFalse()
    {
        $encoder = $this->getPasswordEncoderMock();
        $em = $this->getEntityManagerMock();
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $browser->expects($this->once())
            ->method('getBrowser')
            ->will($this->returnValue('Firefox'));

        $browser->expects($this->once())
            ->method('getPlatform')
            ->will($this->returnValue('Linux'));

        $manager = new Manager($encoder, $em, $browser);

        $session = new Session();
        $session->setNonce('prettyN0nce');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue($session));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Alchemy\Phrasea\Model\Entities\Session'))
            ->will($this->returnValue($repo));

        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with($this->anything(), 'Firefox_Linux', 'prettyN0nce')
            ->will($this->returnValue(false));

        $this->assertFalse($manager->getSession($tokenValue));
    }
    /**
     * @covers Alchemy\Phrasea\Authentication\PersistentCookie\Manager::getSession
     */
    public function testSessionNotFound()
    {
        $encoder = $this->getPasswordEncoderMock();
        $em = $this->getEntityManagerMock();
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $manager = new Manager($encoder, $em, $browser);

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue(null));

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Alchemy\Phrasea\Model\Entities\Session'))
            ->will($this->returnValue($repo));

        $this->assertFalse($manager->getSession($tokenValue));
    }

    private function getPasswordEncoderMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
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
