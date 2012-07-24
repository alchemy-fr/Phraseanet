<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationRootTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/Root.php';
        
        $app['debug'] = true;
        unset($app['exception_handler']);
        
        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function testRouteSlash()
    {
        $crawler = $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRegExp('/^\/login\/\?redirect=[\/a-zA-Z]+/', $response->headers->get('location'));
    }

    public function testRouteRobots()
    {
        $registry = \registry::get_instance();

        $original_value = $registry->get('GV_allow_search_engine');

        $registry->set('GV_allow_search_engine', false, \registry::TYPE_BOOLEAN);

        $crawler = $this->client->request('GET', '/robots.txt');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Disallow: \/$/m', $response->getContent());

        $registry = \registry::get_instance();
        $registry->set('GV_allow_search_engine', true, \registry::TYPE_BOOLEAN);

        $crawler = $this->client->request('GET', '/robots.txt');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Allow: \/$/m', $response->getContent());

        $registry->set('GV_allow_search_engine', $original_value, \registry::TYPE_BOOLEAN);
    }
}
