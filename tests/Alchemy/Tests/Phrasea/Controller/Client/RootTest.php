<?php

namespace Alchemy\Tests\Phrasea\Controller\Client;
use Symfony\Bundle\FrameworkBundle\Client;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
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
