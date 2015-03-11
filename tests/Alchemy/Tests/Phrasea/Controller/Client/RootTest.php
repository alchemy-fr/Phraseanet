<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testGetClient()
    {
        $this->authenticate(self::$DI['app']);
        self::$DI['client']->request("GET", "/client/");
        var_dump(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
