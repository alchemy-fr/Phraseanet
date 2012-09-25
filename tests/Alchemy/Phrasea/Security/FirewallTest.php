<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class FirewallTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRequiredAuth()
    {
        $this->markTestSkipped('Introduce seg fault, to investigate');
        $response = self::$application['firewall']->requireAuthentication(self::$application);
        $this->assertNull($response);
        self::$application->closeAccount();
        $response = self::$application['firewall']->requireAuthentication(self::$application);
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/login/', $response->headers->get('location'));
    }
}
