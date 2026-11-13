<?php

namespace Alchemy\Tests\Phrasea\Security;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class FirewallTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testRequiredAuth()
    {
        $this->assertNull(self::$DI['app']['firewall']->requireAuthentication());
    }

    public function testRequiredAuthNotAuthenticated()
    {
        $this->logout(self::$DI['app']);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', self::$DI['app']['firewall']->requireAuthentication());
    }
}
