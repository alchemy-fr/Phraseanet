<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class FirewallTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRequiredAuth()
    {
        $response = self::$application['firewall']->requireAuthentication($this->app);
        $this->assertNull($response);
        self::$application->closeAccount();
        $response = self::$application['firewall']->requireAuthentication($this->app);
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }
}
