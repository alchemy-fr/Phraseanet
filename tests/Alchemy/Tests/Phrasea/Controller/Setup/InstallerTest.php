<?php

namespace Alchemy\Tests\Phrasea\Controller\Setup;

class InstallerTest extends \PhraseanetWebTestCaseAbstract
{

    public function setUp()
    {
        parent::setUp();

        $environment = 'test';
        return self::$DI['app'] = require __DIR__ . '/FakeSetupApplication.inc';
    }

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['client']->request('GET', '/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/installer/', $response->headers->get('location'));
    }

    public function testRouteInstaller()
    {
        self::$DI['client']->request('GET', '/installer/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(302, $response->getStatusCode(), "test that response is a redirection " . self::$DI['client']->getResponse()->getContent());
        $this->assertEquals('/setup/installer/step2/', $response->headers->get('location'));
    }

    public function testRouteInstallerStep2()
    {
        self::$DI['client']->request('GET', '/installer/step2/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isOk());
    }
}
