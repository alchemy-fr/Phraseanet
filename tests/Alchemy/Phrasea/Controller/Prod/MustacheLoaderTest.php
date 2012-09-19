<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class MustacheLoaderTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $useExceptionHandler = false;

    public function testRouteSlash()
    {
        $this->client->request('GET', '/prod/MustacheLoader/');

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testRouteSlashWrongUrl()
    {
        $this->client->request('GET', '/prod/MustacheLoader/', array('template' => '/../../../../config/config.yml'));

        $response = $this->client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        /* @var $response \Symfony\Component\HttpFoundation\Response */
    }

    public function testRouteSlashWrongFile()
    {
        $this->client->request('GET', '/prod/MustacheLoader/', array('template' => 'patator_lala'));

        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRouteGood()
    {
        $this->client->request('GET', '/prod/MustacheLoader/', array('template' => 'Feedback-Badge'));

        $response = $this->client->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isOk());
    }
}
