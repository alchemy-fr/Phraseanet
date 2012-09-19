<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

class ControllerUpgraderTest extends \PhraseanetWebTestCaseAbstract
{

    protected static function loadApplication()
    {
        $environment = 'test';
        return self::$application = require __DIR__ . '/FakeUpgradeApplication.inc';
    }

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->client->request('GET', '/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/setup/upgrader/', $response->headers->get('location'));
    }

    /**
     * Default route test
     */
    public function testRouteUpgrader()
    {
        $this->client->request('GET', '/upgrader/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Default route test
     */
    public function testRouteStatus()
    {
        $this->client->request('GET', '/upgrader/status/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Default route test
     */
    public function testRouteExecute()
    {
        $this->client->request('POST', '/upgrader/execute/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->headers->get('location'));
    }
}
