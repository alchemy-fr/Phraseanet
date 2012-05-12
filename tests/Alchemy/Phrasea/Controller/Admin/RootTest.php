<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class RootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';
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
        $this->client->request('GET', '/', array('section' => 'base:featured'));
        $this->assertTrue($this->client->getResponse()->isOk());

        $this->client->request('GET', '/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }
}
