<?php

namespace Alchemy\Tests\Phrasea\Authentication\PersistentCookie;

use Alchemy\Phrasea\Authentication\PersistentCookie\Manager;
use Entities\Session;

class ManagerTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\PersistentCookie\Manager::getSession
     */
    public function testGetSession()
    {
        $encoder = $this->getPasswordEncoderMock();
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $browser->expects($this->once())
            ->method('getBrowser')
            ->will($this->returnValue('Firefox'));

        $browser->expects($this->once())
            ->method('getPlatform')
            ->will($this->returnValue('Linux'));

        $session = new Session();
        $session->setNonce('prettyN0nce');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue($session));

        $manager = new Manager($encoder, $repo, $browser);

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
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $browser->expects($this->once())
            ->method('getBrowser')
            ->will($this->returnValue('Firefox'));

        $browser->expects($this->once())
            ->method('getPlatform')
            ->will($this->returnValue('Linux'));

        $session = new Session();
        $session->setNonce('prettyN0nce');

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue($session));

        $manager = new Manager($encoder, $repo, $browser);

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
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue(null));

        $manager = new Manager($encoder, $repo, $browser);

        $this->assertFalse($manager->getSession($tokenValue));
    }

    private function getPasswordEncoderMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder')
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
