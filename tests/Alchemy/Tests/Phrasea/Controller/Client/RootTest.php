<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testGetClient()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['client']->request("GET", "/client/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
