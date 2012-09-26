<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

class ControllerUpgraderTest extends \PhraseanetWebTestCaseAbstract
{

    public function setUp()
    {
        parent::setUp();

        $environment = 'test';
        return self::$DI['app'] = require __DIR__ . '/FakeUpgradeApplication.inc';
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
        $this->assertEquals('/setup/upgrader/', $response->headers->get('location'));
    }

    /**
     * Default route test
     */
    public function testRouteUpgrader()
    {
        self::$DI['client']->request('GET', '/upgrader/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Default route test
     */
    public function testRouteStatus()
    {
        self::$DI['client']->request('GET', '/upgrader/status/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Default route test
     */
    public function testRouteExecute()
    {
        self::$DI['client']->request('POST', '/upgrader/execute/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->headers->get('location'));
    }
}
