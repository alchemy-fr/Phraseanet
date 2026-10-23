<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;
use Symfony\Component\HttpKernel\Client;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->authenticate(self::$DI['app']);

        /** @var Client $client */
        $client = self::$DI['client'];

        $client->request('GET', '/admin/', ['section' => 'base:featured']);
        $this->assertTrue($client->getResponse()->isOk());

        $client->request('GET', '/admin/');
        $this->assertTrue($client->getResponse()->isOk());
    }
}
