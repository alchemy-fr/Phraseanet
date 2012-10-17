<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class FirewallTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRequiredAuth()
    {
        $res = self::$DI['app']['firewall']->requireAuthentication(self::$DI['app']);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Security\\Firewall', $res);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function testRequiredAuthNotAuthenticated()
    {
        self::$DI['app']->closeAccount();
        self::$DI['app']['firewall']->requireAuthentication(self::$DI['app']);
    }
}
