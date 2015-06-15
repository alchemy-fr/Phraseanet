<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

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

        self::$DI['client']->request('GET', '/admin/', ['section' => 'base:featured']);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        self::$DI['client']->request('GET', '/admin/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
