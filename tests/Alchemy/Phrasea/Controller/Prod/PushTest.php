<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ControllerPushTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
    public function testRoutePOSTSendSlash()
    {
        $route = '/push/sendform/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTValidateSlash()
    {
        $route = '/push/validateform/';

        $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTsend()
    {
        $route = '/push/send/';

        $records = array(
            static::$records['record_1']->get_serialize_key(),
            static::$records['record_2']->get_serialize_key(),
        );

        $receivers = array(
            array('usr_id' => self::$user_alt1->get_id(), 'HD'     => 1)
            , array('usr_id' => self::$user_alt2->get_id(), 'HD'     => 0)
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
        $route = '/push/validate/';

        $records = array(
            static::$records['record_1']->get_serialize_key(),
            static::$records['record_2']->get_serialize_key(),
        );

        $participants = array(
            array(
                'usr_id'     => self::$user_alt1->get_id(),
                'agree'      => 0,
                'see_others' => 1,
                'HD'         => 0,
            )
            , array(
                'usr_id'     => self::$user_alt2->get_id(),
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
        $route = '/push/search-user/';

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
