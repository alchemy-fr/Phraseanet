<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Admin\ConnectedUsers;

class ConnectedUserTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\ConnectedUsers::connect
     */
    public function testgetSlash()
    {
        $this->client->request('GET', '/connected-users/');
        $this->assertTrue($this->client->getResponse()->isOk());
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
