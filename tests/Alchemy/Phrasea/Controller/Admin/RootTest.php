<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class RootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->client->request('GET', '/admin/', array('section' => 'base:featured'));
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->request('GET', '/admin/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }
}
