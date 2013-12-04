<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class PushTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * Default route test
     */
    public function testRoutePOSTSendSlash()
    {
        $route = '/prod/push/sendform/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTValidateSlash()
    {
        $route = '/prod/push/validateform/';

        self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTsend()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived', 2);
        $this->mockUserNotificationSettings('eventsmanager_notify_push');

        $route = '/prod/push/send/';

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
        ];

        self::$DI['client']->request('POST', $route, [
            'lst'          => implode(';', $records),
            'participants' => [
                ['usr_id' => self::$DI['user_alt1']->getId(), 'HD'     => 1],
                ['usr_id' => self::$DI['user_alt2']->getId(), 'HD'     => 0]
            ]
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);

        $this->assertTrue($datas['success'], 'Result is successful');
    }

    public function testRoutePOSTvalidate()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest', 3);
        $this->mockUserNotificationSettings('eventsmanager_notify_validate');

        $route = '/prod/push/validate/';

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
        ];

        self::$DI['client']->request('POST', $route, [
            'lst'          => implode(';', $records),
            'participants' => [[
                'usr_id'     => self::$DI['user_alt1']->getId(),
                'agree'      => 0,
                'see_others' => 1,
                'HD'         => 0,
            ], [
                'usr_id'     => self::$DI['user_alt2']->getId(),
                'agree'      => 1,
                'see_others' => 0,
                'HD'         => 1,
            ]]
        ]);

        $response = self::$DI['client']->getResponse();

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

        $params = [
            'query' => ''
        ];

        self::$DI['client']->request('GET', $route, $params);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());

        $datas = (array) json_decode($response->getContent());

        $this->assertTrue(is_array($datas), 'Json is valid');
    }
}
