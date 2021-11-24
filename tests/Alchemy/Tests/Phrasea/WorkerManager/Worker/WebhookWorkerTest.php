<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\WorkerManager\Worker\WebhookWorker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class WebhookWorkerTest extends \PhraseanetTestCase
{
    public function testDeliverDataWithoutRestriction()
    {
        $em = self::$DI['app']['orm.em'];
        $webhookUrl = 'http://webhook.com/webhook/';

        $event = new WebhookEvent();
        $event->setName('record.created')
            ->setType('record')
            ->setData([
                'databox_id'        => self::$DI['record_1']->get_sbas_id(),
                'record_id'         => self::$DI['record_1']->get_record_id(),
                'collection_name'   => self::$DI['record_1']->getCollection()->get_name(),
                'record_type'       => 'record'
            ])
            ->setCollectionBaseIds([])
            ;

        $em->persist($event);

        $em->flush();

        self::$DI['app']['manipulator.api-application']->setWebhookUrl(self::$DI['oauth2-app-user1'], $webhookUrl);

        $payload = [
            'id'    => $event->getId(),
            'published' => time()
        ];

        $client = new Client();

        $this->deliverEventTest($client, $event, $payload);

        return $event;
    }

    private function deliverEventTest(Client $client, WebhookEvent $event, $payload)
    {
        $webhookWorker = new WebhookWorker(self::$DI['app']);
        $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
        $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

        $requestResult = $webhookWorker->deliverEvent($client, [self::$DI['oauth2-app-user1']], $event, $payload);

        $this->assertCount(1, $requestResult);

        $deliveryCreated = self::$DI['app']['repo.webhook-delivery']->findOneBy(['event'  => $event]);
        $deliveryId = $deliveryCreated != null ? $deliveryCreated->getId() : null;

        $this->assertEquals($deliveryId, key($requestResult));

        /** @var  Request $request */
        foreach ($requestResult as $request) {
            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('http://webhook.com/webhook/'."#".$deliveryId, $request->getUri());
            $this->assertArrayHasKey('Content-Type', $request->getHeaders());
            $this->assertContains('application/vnd.phraseanet.event+json', $request->getHeader('Content-Type'));
            $requestBody = json_decode($request->getBody()->__tostring(), true);

            $this->assertEquals('record.created', $requestBody['event']);
            $this->assertEquals(self::$DI['record_1']->get_sbas_id(), $requestBody['data']['databox_id']);
            $this->assertEquals(self::$DI['record_1']->get_record_id(), $requestBody['data']['record_id']);
            $this->assertEquals('record', $requestBody['data']['record_type']);
            $this->assertArrayHasKey('time', $requestBody['data']);
        }
    }

    /**
     * @depends testDeliverDataWithoutRestriction
     *
     * @param WebhookEvent $event
     */
    public function testNoDeliverWithSpecifiedListenedEvent(WebhookEvent $event)
    {
        self::$DI['oauth2-app-user1']->setListenedEvents(['record.edited']);
        $payload = [
            'id'    => $event->getId(),
            'published' => time()
        ];

        $client = new Client();

        $webhookWorker = new WebhookWorker(self::$DI['app']);
        $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
        $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

        $requestResult = $webhookWorker->deliverEvent($client, [self::$DI['oauth2-app-user1']], $event, $payload);

        // normally no request sended because record.created sended
        $this->assertCount(0, $requestResult);
    }

    public function testNoDeliverWithCreatorNoCollectionRight()
    {
        $em = self::$DI['app']['orm.em'];

        // revoke user creator access for the record collection
        self::$DI['app']->getAclForUser(self::$DI['user_1'])->revoke_access_from_bases([self::$DI['record_1']->getBaseId()]);

        $event = new WebhookEvent();
        $event->setName('record.created')
            ->setType('record')
            ->setData([
                'databox_id'        => self::$DI['record_1']->get_sbas_id(),
                'record_id'         => self::$DI['record_1']->get_record_id(),
                'collection_name'   => self::$DI['record_1']->getCollection()->get_name(),
                'record_type'       => 'record'
            ])
            ->setCollectionBaseIds([self::$DI['record_1']->getBaseId()])
        ;

        $em->persist($event);

        $em->flush();

        $payload = [
            'id'    => $event->getId(),
            'published' => time()
        ];

        $client = new Client();

        $webhookWorker = new WebhookWorker(self::$DI['app']);
        $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
        $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

        $requestResult = $webhookWorker->deliverEvent($client, [self::$DI['oauth2-app-user1']], $event, $payload);

        // normally no request sended because user creator have no access to the record collection
        $this->assertCount(0, $requestResult);

        // restitute the old right for others test
        self::$DI['app']->getAclForUser(self::$DI['user_1'])->give_access_to_base([self::$DI['record_1']->getBaseId()]);
    }

    public function testAllEventWithoutRestrictions()
    {
        $em = self::$DI['app']['orm.em'];
        $events = [
            WebhookEvent::RECORD_TYPE   => [
                WebhookEvent::RECORD_CREATED,
                WebhookEvent::RECORD_EDITED,
                WebhookEvent::RECORD_DELETED,
                WebhookEvent::RECORD_MEDIA_SUBSTITUTED,
                WebhookEvent::RECORD_COLLECTION_CHANGED,
                WebhookEvent::RECORD_STATUS_CHANGED,
            ],
            WebhookEvent::RECORD_SUBDEF_TYPE    => [
                WebhookEvent::RECORD_SUBDEF_CREATED,
                WebhookEvent::RECORD_SUBDEF_FAILED,
            ],
            WebhookEvent::USER_TYPE     => [
                WebhookEvent::USER_CREATED,
                WebhookEvent::USER_DELETED,
            ],
            // ??
            WebhookEvent::USER_REGISTRATION_TYPE    => [
                WebhookEvent::USER_REGISTRATION_GRANTED,
                WebhookEvent::USER_REGISTRATION_REJECTED,
            ],
            WebhookEvent::FEED_ENTRY_TYPE   => [
                WebhookEvent::NEW_FEED_ENTRY,
            ],
            WebhookEvent::ORDER_TYPE    => [
                WebhookEvent::ORDER_CREATED,
                WebhookEvent::ORDER_DELIVERED,
                WebhookEvent::ORDER_DENIED
            ]
        ];

        $order = new Order();
        $order
            ->setUser(self::$DI['user_notAdmin'])
            ->setOrderUsage('test')
            ->setDeadline(new \DateTime('+1 day'))
        ;
        $em->persist($order);
        $em->flush();

        $eventsData = [
            WebhookEvent::RECORD_TYPE   => [
                'databox_id'        => self::$DI['record_1']->get_sbas_id(),
                'record_id'         => self::$DI['record_1']->get_record_id(),
                'collection_name'   => self::$DI['record_1']->getCollection()->get_name(),
                'record_type'       => 'record'
            ],
            WebhookEvent::RECORD_SUBDEF_TYPE    => [
                'databox_id'    => self::$DI['record_1']->get_sbas_id(),
                'record_id'     => self::$DI['record_1']->get_record_id(),
                'subdef'        => 'thumbnail'
            ],
            WebhookEvent::USER_TYPE     => [
                'user_id' => self::$DI['user_notAdmin']->getId(),
                'email'   => 'noone_not_admin@example.com',
                'login'   => 'noone_not_admin@example.com'
            ],
            WebhookEvent::USER_REGISTRATION_TYPE    => [
                [
                    'user_id'  => self::$DI['user_notAdmin']->getId(),
                    'granted'  => ['granted'],
                    'rejected' => ['rejected']
                ],
            ],
            WebhookEvent::FEED_ENTRY_TYPE   => [
                'entry_id' => self::$DI['feed_public_entry']->getId(),
                'feed_id'  => self::$DI['feed_public']->getId()
            ],
            WebhookEvent::ORDER_TYPE    => [
                'order_id' => $order->getId(),
                'user_id' => self::$DI['user_notAdmin']->getId(),
            ]
        ];


        $client = new Client();

        $webhookWorker = new WebhookWorker(self::$DI['app']);
        $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
        $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

        foreach ($events as $type => $tEvent) {
            foreach ($tEvent as $eventName) {
                $event = new WebhookEvent();
                $event
                    ->setName($eventName)
                    ->setType($type)
                    ->setData($eventsData[$type])
                    ->setCollectionBaseIds([])
                ;

                $em->persist($event);
                $em->flush();

                $payload = [
                    'id'    => $event->getId(),
                    'published' => time()
                ];

                $requestResult = $webhookWorker->deliverEvent($client, [self::$DI['oauth2-app-user1']], $event, $payload);
                $this->assertCount(1, $requestResult);

                /** @var  Request $request */
                $request =  current($requestResult);
                $requestBody = json_decode($request->getBody()->__tostring(), true);

                switch (true) {
                    case (in_array($requestBody['event'], $events[WebhookEvent::RECORD_TYPE])):
                        $this->assertArrayHasKey('data', $requestBody);
                        $this->assertEquals(self::$DI['record_1']->get_sbas_id(), $requestBody['data']['databox_id']);
                        $this->assertEquals(self::$DI['record_1']->get_record_id(), $requestBody['data']['record_id']);
                        $this->assertEquals('record', $requestBody['data']['record_type']);
                        $this->assertEquals(self::$DI['record_1']->getCollection()->get_name(), $requestBody['data']['collection_name']);
                        $this->assertArrayHasKey('time', $requestBody['data']);

                        break;
                    case (in_array($requestBody['event'], $events[WebhookEvent::RECORD_SUBDEF_TYPE])):
                        $this->assertArrayHasKey('data', $requestBody);
                        $this->assertEquals(self::$DI['record_1']->get_sbas_id(), $requestBody['data']['databox_id']);
                        $this->assertEquals(self::$DI['record_1']->get_record_id(), $requestBody['data']['record_id']);
                        $this->assertEquals('thumbnail', $requestBody['data']['subdef']);

                        break;
                    case (in_array($requestBody['event'], $events[WebhookEvent::USER_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('user', $requestBody);
                        $this->assertEquals(self::$DI['user_notAdmin']->getId(), $requestBody['user']['id']);
                        $this->assertEquals('noone_not_admin@example.com', $requestBody['user']['email']);
                        $this->assertEquals('noone_not_admin@example.com', $requestBody['user']['login']);

                        break;
                    case (in_array($requestBody['event'], $events[WebhookEvent::USER_REGISTRATION_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('user', $requestBody);
                        $this->assertArrayHasKey('granted', $requestBody);
                        $this->assertArrayHasKey('rejected', $requestBody);
                        $this->assertEquals(self::$DI['user_notAdmin']->getId(), $requestBody['user']['id']);
                        $this->assertEquals(['rejected'], $requestBody['rejected']);
                        $this->assertEquals(['granted'], $requestBody['granted']);

                        break;
                    case (in_array($requestBody['event'], $events[WebhookEvent::FEED_ENTRY_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('feed', $requestBody);
                        $this->assertArrayHasKey('entry', $requestBody);
                        $this->assertEquals(self::$DI['feed_public_entry']->getId(), $requestBody['entry']['id']);

                        break;
                    case (in_array($requestBody['event'], $events[WebhookEvent::ORDER_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('user', $requestBody);
                        $this->assertArrayHasKey('order', $requestBody);
                        $this->assertEquals(self::$DI['user_notAdmin']->getId(), $requestBody['user']['id']);

                        break;
                }
            }
        }
    }
}
