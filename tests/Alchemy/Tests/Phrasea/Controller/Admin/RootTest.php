<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

class RootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['app']['authentication']->openAccount(self::$DI['user']);

        self::$DI['client']->request('GET', '/admin/', array('section' => 'base:featured'));
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        self::$DI['client']->request('GET', '/admin/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
