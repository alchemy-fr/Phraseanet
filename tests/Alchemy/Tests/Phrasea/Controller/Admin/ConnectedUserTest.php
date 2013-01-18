<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Admin\ConnectedUsers;

class ConnectedUserTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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

     /**
     * @covers \Alchemy\Phrasea\Controller\Admin\ConnectedUsers::appName
     */
    public function testAppName()
    {
        $appNameResult = ConnectedUsers::appName(1000);
        $this->assertNull($appNameResult);
        $appNameResult = ConnectedUsers::appName(0);
        $this->assertTrue(is_string($appNameResult));
    }

}
