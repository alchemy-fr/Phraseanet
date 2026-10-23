<?php

namespace Alchemy\Tests\Phrasea\Authentication\PersistentCookie;

use Alchemy\Phrasea\Authentication\PersistentCookie\Manager;
use Alchemy\Phrasea\Model\Entities\Session;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ManagerTest extends \PhraseanetTestCase
{
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

        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

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

        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

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
    public function testSessionNotFound()
    {
        $encoder = $this->getPasswordEncoderMock();
        $browser = $this->getBrowserMock();
        $tokenValue = 'encrypted-persistent-value';

        $repo = $this->createMock('Doctrine\ORM\EntityRepository');

        $repo->expects($this->once())
            ->method('findOneBy')
            ->with($this->equalTo(['token' => $tokenValue]))
            ->will($this->returnValue(null));

        $manager = new Manager($encoder, $repo, $browser);

        $this->assertFalse($manager->getSession($tokenValue));
    }

    private function getPasswordEncoderMock()
    {
        return $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder');
    }

    private function getBrowserMock()
    {
        return $this->createMock('Browser');
    }
}
