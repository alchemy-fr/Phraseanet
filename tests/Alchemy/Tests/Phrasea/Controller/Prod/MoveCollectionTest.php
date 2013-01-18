<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class ControllerMoveCollectionTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['client']->request('POST', '/prod/records/movecollection/', array('lst' => self::$DI['record_1']->get_serialize_key()));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testApply()
    {

        self::$DI['client']->request('POST', '/prod/records/movecollection/apply/', array('lst'     => self::$DI['record_1']->get_serialize_key(), 'base_id' => self::$DI['collection']->get_base_id()));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}
