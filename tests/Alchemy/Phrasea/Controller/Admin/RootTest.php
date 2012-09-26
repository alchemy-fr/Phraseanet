<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class RootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['client']->request('GET', '/admin/', array('section' => 'base:featured'));
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        self::$DI['client']->request('GET', '/admin/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }
}
