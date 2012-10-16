<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class FirewallTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRequiredAuth()
    {
        $response = self::$DI['app']['firewall']->requireAuthentication(self::$DI['app']);
        $this->assertNull($response);
        self::$DI['app']->closeAccount();
        $response = self::$DI['app']['firewall']->requireAuthentication(self::$DI['app']);
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }
}
