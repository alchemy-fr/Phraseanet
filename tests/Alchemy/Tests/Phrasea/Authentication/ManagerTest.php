<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Manager;

class ManagerTest extends \PhraseanetTestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\Manager::openAccount
     */
    public function testOpenAccount()
    {
        $authenticator = $this->getAuthenticatorMock();
        $providers = $this->getProvidersMock();

        $user = $this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->disableOriginalConstructor()
            ->getMock();

        $session = $this->getMock('Alchemy\Phrasea\Model\Entities\Session');

        $authenticator->expects($this->once())
            ->method('openAccount')
            ->with($this->equalTo($user))
            ->will($this->returnValue($session));

        $manager = new Manager($authenticator, $providers);
        $this->assertEquals($session, $manager->openAccount($user));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Manager::authenticate
     */
    public function testAuthenticate()
    {
        $authenticator = $this->getAuthenticatorMock();
        $providers = $this->getProvidersMock();

        $providerName = 'roro-provider';
        $parameters = ['key' => 'value'];
        $provider = $this->getMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');

        $providers->expects($this->once())
            ->method('get')
            ->with($this->equalTo($providerName))
            ->will($this->returnValue($provider));

        $redirect = $this->getMockBuilder('Symfony\Component\HttpFoundation\RedirectResponse')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($parameters))
            ->will($this->returnValue($redirect));

        $manager = new Manager($authenticator, $providers);
        $this->assertEquals($redirect, $manager->authenticate($parameters, $providerName));
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Manager::getProviders
     */
    public function testGetProviders()
    {
        $authenticator = $this->getAuthenticatorMock();
        $providers = $this->getProvidersMock();

        $manager = new Manager($authenticator, $providers);
        $this->assertEquals($providers, $manager->getProviders());
    }

    private function getProvidersMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\ProvidersCollection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getAuthenticatorMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Authentication\Authenticator')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
