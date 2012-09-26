<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerPushTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRoutePOSTSendSlash()
    {
        $route = '/prod/push/sendform/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTValidateSlash()
    {
        $route = '/prod/push/validateform/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTsend()
    {
        $route = '/prod/push/send/';

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
        );

        $receivers = array(
            array('usr_id' => self::$DI['user_alt1']->get_id(), 'HD'     => 1)
            , array('usr_id' => self::$DI['user_alt2']->get_id(), 'HD'     => 0)
        );

        $params = array(
            'lst'          => implode(';', $records)
            , 'participants' => $receivers
        );

        $this->client->request('POST', $route, $params);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);

        $this->assertTrue($datas['success'], 'Result is successful');
    }

    public function testRoutePOSTvalidate()
    {
        $route = '/prod/push/validate/';

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
        );

        $participants = array(
            array(
                'usr_id'     => self::$DI['user_alt1']->get_id(),
                'agree'      => 0,
                'see_others' => 1,
                'HD'         => 0,
            )
            , array(
                'usr_id'     => self::$DI['user_alt2']->get_id(),
                'agree'      => 1,
                'see_others' => 0,
                'HD'         => 1,
            )
        );

        $params = array(
            'lst'          => implode(';', $records)
            , 'participants' => $participants
        );

        $this->client->request('POST', $route, $params);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);

        $this->assertTrue($datas['success'], 'Result is successful');
    }

    public function testRouteGETsearchuser()
    {
        $route = '/prod/push/search-user/';

        $params = array(
            'query' => ''
        );

        $this->client->request('GET', $route, $params);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue(is_array($datas), 'Json is valid');
    }
}
