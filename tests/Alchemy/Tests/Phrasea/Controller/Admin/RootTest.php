<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
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
