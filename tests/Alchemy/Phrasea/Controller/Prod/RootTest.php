<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerRootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $crawler = $this->client->request('GET', '/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }
}
