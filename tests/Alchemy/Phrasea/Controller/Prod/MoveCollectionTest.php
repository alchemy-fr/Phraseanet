<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerMoveCollectionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
        $this->client->request('POST', '/records/movecollection/', array('lst' => static::$records['record_1']->get_serialize_key()));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testApply()
    {

        $this->client->request('POST', '/records/movecollection/apply/', array('lst'     => static::$records['record_1']->get_serialize_key(), 'base_id' => self::$collection->get_base_id()));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }
}
