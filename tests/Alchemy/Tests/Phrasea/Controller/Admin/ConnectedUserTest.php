<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class ConnectedUserTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\ConnectedUsers::connect
     */
    public function testgetSlash()
    {
        self::$DI['client']->request('GET', '/admin/connected-users/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
