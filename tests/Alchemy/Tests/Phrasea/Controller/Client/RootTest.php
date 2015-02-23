<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testGetClient()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea2 is required for this test');
        }

        $this->authenticate(self::$DI['app']);
        self::$DI['client']->request("GET", "/client/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
