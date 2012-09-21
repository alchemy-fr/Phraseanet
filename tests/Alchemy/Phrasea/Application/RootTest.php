<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ApplicationRootTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRouteSlash()
    {
        $crawler = $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertRegExp('/^\/login\/\?redirect=[\/a-zA-Z]+/', $response->headers->get('location'));
    }

    public function testRouteRobots()
    {
        $original_value = $this->app['phraseanet.registry']->get('GV_allow_search_engine');

        $this->app['phraseanet.registry']->set('GV_allow_search_engine', false, \registry::TYPE_BOOLEAN);

        $crawler = $this->client->request('GET', '/robots.txt');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Disallow: \/$/m', $response->getContent());

        $this->app['phraseanet.registry']->set('GV_allow_search_engine', true, \registry::TYPE_BOOLEAN);

        $crawler = $this->client->request('GET', '/robots.txt');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertEquals('UTF-8', $response->getCharset());

        $this->assertRegExp('/^Allow: \/$/m', $response->getContent());

        $this->app['phraseanet.registry']->set('GV_allow_search_engine', $original_value, \registry::TYPE_BOOLEAN);
    }
}
