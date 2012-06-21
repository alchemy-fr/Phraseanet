<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

class ControllerLanguageTest extends PhraseanetWebTestCaseAbstract
{
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function testRootPost()
    {
        $route = '/language/';

        $this->client->request("GET", $route);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
    }
}
