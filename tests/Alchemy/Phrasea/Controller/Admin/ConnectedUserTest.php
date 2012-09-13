<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\Admin\ConnectedUsers;

class ConnectedUserTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

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
        $this->client->request('GET', '/admin/connected-users/');
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
