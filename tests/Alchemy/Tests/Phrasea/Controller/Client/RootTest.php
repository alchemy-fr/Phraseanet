<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testGetClient()
    {
        $this->authenticate(self::$DI['app']);
        /** @var Client $client */
        $client = self::$DI['client'];
        $client->request("GET", "/client/");
        $this->assertTrue($client->getResponse()->isRedirect());
    }
}
