<?php

namespace Alchemy\Tests\Phrasea\Security;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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
