<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class PushTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testRoutePOSTSendSlash()
    {
        self::$DI['client']->request('POST', '/prod/push/sendform/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTValidateSlash()
    {
        self::$DI['client']->request('POST', '/prod/push/validateform/');
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }

    public function testRoutePOSTSend()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoPushReceived', 2);
        $this->mockUserNotificationSettings('eventsmanager_notify_push');
        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
        ];
        self::$DI['client']->request('POST', '/prod/push/send/', [
            'lst'          => implode(';', $records),
            'participants' => [
                ['usr_id' => self::$DI['user_alt1']->getId(), 'HD'     => 1],
                ['usr_id' => self::$DI['user_alt2']->getId(), 'HD'     => 0]
            ]
        ]);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
        $data = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success'], 'Result is successful');
    }

    public function testRoutePOSTValidate()
    {
        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoValidationRequest', 3);
        $this->mockUserNotificationSettings('eventsmanager_notify_validate');

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
        ];

        self::$DI['client']->request('POST', '/prod/push/validate/', [
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
        $data = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success'], 'Result is successful');
    }

    public function testRouteGETSearchUser()
    {
        self::$DI['client']->request('GET',  '/prod/push/search-user/', [
            'query' => ''
        ]);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
        $data = (array) json_decode($response->getContent());
        $this->assertTrue(is_array($data), 'Json is valid');
    }
}
