<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\Manager;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ManagerTest extends \PhraseanetTestCase
{
    public function testOpenAccount()
    {
        $authenticator = $this->getAuthenticatorMock();
        $providers = $this->getProvidersMock();

        $user = $this->createUserMock();

        $session = $this->createStub('Alchemy\Phrasea\Model\Entities\Session');

        $authenticator->expects($this->once())
            ->method('openAccount')
            ->with($this->equalTo($user))
            ->will($this->returnValue($session));

        $manager = new Manager($authenticator, $providers);
        $this->assertEquals($session, $manager->openAccount($user));
    }

    public function testAuthenticate()
    {
        $authenticator = $this->getAuthenticatorMock();
        $providers = $this->getProvidersMock();

        $providerName = 'roro-provider';
        $parameters = ['key' => 'value'];
        $provider = $this->createMock('Alchemy\Phrasea\Authentication\Provider\ProviderInterface');

        $providers->expects($this->once())
            ->method('get')
            ->with($this->equalTo($providerName))
            ->will($this->returnValue($provider));

        $redirect = $this->createStub('Symfony\Component\HttpFoundation\RedirectResponse');

        $provider->expects($this->once())
            ->method('authenticate')
            ->with($this->equalTo($parameters))
            ->will($this->returnValue($redirect));

        $manager = new Manager($authenticator, $providers);
        $this->assertEquals($redirect, $manager->authenticate($parameters, $providerName));
    }

    public function testGetProviders()
    {
        $authenticator = $this->getAuthenticatorMock();
        $providers = $this->getProvidersMock();

        $manager = new Manager($authenticator, $providers);
        $this->assertEquals($providers, $manager->getProviders());
    }

    private function getProvidersMock()
    {
        return $this->createMock('Alchemy\Phrasea\Authentication\ProvidersCollection');
    }

    private function getAuthenticatorMock()
    {
        return $this->createMock('Alchemy\Phrasea\Authentication\Authenticator');
    }
}
