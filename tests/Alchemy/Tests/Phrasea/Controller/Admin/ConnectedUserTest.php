<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\ConnectedUsers::class, 'connect')]
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class ConnectedUserTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testgetSlash()
    {
        self::$DI['client']->request('GET', '/admin/connected-users/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
