<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class MustacheLoaderTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function testRouteSlash()
    {

        $this->client->request('GET', '/MustacheLoader/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($response->isOk());

        $this->client->request('GET', '/MustacheLoader/', array('template' => '/../../../../config/config.yml'));

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($response->isOk());

        $this->client->request('GET', '/MustacheLoader/', array('template' => 'patator_lala'));

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($response->isOk());

        $this->client->request('GET', '/MustacheLoader/', array('template' => 'Feedback-Badge'));

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isOk());
    }
}
