<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerEditTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->client->request('POST', '/prod/records/edit/', array('lst' => static::$records['record_1']->get_serialize_key()));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testApply()
    {
        $this->client->request('POST', '/prod/records/edit/apply/', array('lst' => static::$records['record_1']->get_serialize_key()));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    public function testVocabulary()
    {
        $this->client->request('GET', '/prod/records/edit/vocabulary/Zanzibar/');

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $datas = json_decode($response->getContent());
        $this->assertFalse($datas->success);

        $this->client->request('GET', '/prod/records/edit/vocabulary/User/');

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $datas = json_decode($response->getContent());
        $this->assertFalse($datas->success);

        $params = array('sbas_id' => self::$collection->get_sbas_id());
        $this->client->request('GET', '/prod/records/edit/vocabulary/Zanzibar/', $params);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $datas = json_decode($response->getContent());
        $this->assertFalse($datas->success);

        $params = array('sbas_id' => self::$collection->get_sbas_id());
        $this->client->request('GET', '/prod/records/edit/vocabulary/User/', $params);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $datas = json_decode($response->getContent());
        $this->assertTrue($datas->success);
    }
}
