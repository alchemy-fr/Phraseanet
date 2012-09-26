<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerMoveCollectionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->client->request('POST', '/prod/records/movecollection/', array('lst' => self::$DI['record_1']->get_serialize_key()));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testApply()
    {

        $this->client->request('POST', '/prod/records/movecollection/apply/', array('lst'     => self::$DI['record_1']->get_serialize_key(), 'base_id' => self::$collection->get_base_id()));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }
}
