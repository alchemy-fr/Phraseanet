<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\WorkerManager\Worker\WebhookWorker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class WebhookWorkerTest extends \PhraseanetTestCase
{
    private $events = [
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

    private $eventsData;

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

        //minimum the app user access to the record collection
        self::$DI['app']->getAclForUser(self::$DI['user_1'])->give_access_to_base([self::$DI['record_1']->getBaseId()]);
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
            $this->assertArrayHasKey('time', $requestBody);
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

    /**
     * @depends testDeliverDataWithoutRestriction
     */
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

    public function testDeliverAllEventWithoutBaseIdRestrictions()
    {
        $this->loadEventsData();
        // add right canadmin for a creatorUser in at least one collection
        // needed for test user.created and deleting fontome user.deleted
        self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_base(self::$DI['record_1']->getBaseId(), [\ACL::CANADMIN => true]);

        $em = self::$DI['app']['orm.em'];
        $client = new Client();

        $webhookWorker = new WebhookWorker(self::$DI['app']);
        $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
        $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

        foreach ($this->events as $type => $tEvent) {
            foreach ($tEvent as $eventName) {
                $event = new WebhookEvent();
                $event
                    ->setName($eventName)
                    ->setType($type)
                    ->setData($this->eventsData[$type])
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

                $this->assertInternalType('array', $requestBody);
                $this->assertArrayHasKey('event', $requestBody);
                $this->assertArrayHasKey('time', $requestBody);

                switch (true) {
                    case (in_array($requestBody['event'], $this->events[WebhookEvent::RECORD_TYPE])):
                        $this->assertArrayHasKey('data', $requestBody);
                        $this->assertEquals(self::$DI['record_1']->get_sbas_id(), $requestBody['data']['databox_id']);
                        $this->assertEquals(self::$DI['record_1']->get_record_id(), $requestBody['data']['record_id']);
                        $this->assertEquals('record', $requestBody['data']['record_type']);
                        $this->assertEquals(self::$DI['record_1']->getCollection()->get_name(), $requestBody['data']['collection_name']);
                        $this->assertCount(6, $requestBody);

                        break;
                    case (in_array($requestBody['event'], $this->events[WebhookEvent::RECORD_SUBDEF_TYPE])):
                        $this->assertArrayHasKey('data', $requestBody);
                        $this->assertEquals(self::$DI['record_1']->get_sbas_id(), $requestBody['data']['databox_id']);
                        $this->assertEquals(self::$DI['record_1']->get_record_id(), $requestBody['data']['record_id']);
                        $this->assertEquals('thumbnail', $requestBody['data']['subdef']);
                        $this->assertCount(6, $requestBody);

                        break;
                    case (in_array($requestBody['event'], $this->events[WebhookEvent::USER_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('user', $requestBody);
                        $this->assertEquals(self::$DI['user_notAdmin']->getId(), $requestBody['user']['id']);
                        $this->assertEquals('noone_not_admin@example.com', $requestBody['user']['email']);
                        $this->assertEquals('noone_not_admin@example.com', $requestBody['user']['login']);
                        $this->assertCount(6, $requestBody);

                        break;
                    case (in_array($requestBody['event'], $this->events[WebhookEvent::USER_REGISTRATION_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('user', $requestBody);
                        $this->assertArrayHasKey('granted', $requestBody);
                        $this->assertArrayHasKey('rejected', $requestBody);
                        $this->assertEquals(self::$DI['user_notAdmin']->getId(), $requestBody['user']['id']);
                        $this->assertEquals(['rejected'], $requestBody['rejected']);
                        $this->assertEquals(['granted'], $requestBody['granted']);
                        $this->assertCount(8, $requestBody);

                        break;
                    case (in_array($requestBody['event'], $this->events[WebhookEvent::FEED_ENTRY_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('feed', $requestBody);
                        $this->assertArrayHasKey('entry', $requestBody);
                        $this->assertEquals(self::$DI['feed_public_entry']->getId(), $requestBody['entry']['id']);
                        $this->assertCount(9, $requestBody);

                        break;
                    case (in_array($requestBody['event'], $this->events[WebhookEvent::ORDER_TYPE])):
                        $this->assertArrayNotHasKey('data', $requestBody);
                        $this->assertArrayHasKey('user', $requestBody);
                        $this->assertArrayHasKey('order', $requestBody);
                        $this->assertEquals(self::$DI['user_notAdmin']->getId(), $requestBody['user']['id']);
                        $this->assertCount(7, $requestBody);

                        break;
                }
            }
        }
    }

    public function testDeliverWithValidRight()
    {
        $this->loadEventsData();

        $em = self::$DI['app']['orm.em'];
        foreach ($this->events as $type => $tEvent) {
            foreach ($tEvent as $eventName) {
                $event = new WebhookEvent();
                $event
                    ->setName($eventName)
                    ->setType($type)
                    ->setData($this->eventsData[$type])
                    ->setCollectionBaseIds([self::$DI['record_1']->getBaseId()])
                ;

                $em->persist($event);
                $em->flush();

                list($rightSbas, $rightBase) = $this->getUserActiveRights(WebhookEvent::$eventsAccessRight, $eventName);

                $rightSbasReset = [
                    \ACL::BAS_MODIFY_STRUCT => false,
                    \ACL::BAS_MODIF_TH      => false,
                    \ACL::BAS_CHUPUB        => false,
                    \ACL::BAS_MANAGE        => false,
                ];

                // re-initialize user right
                self::$DI['app']->getAclForUser(self::$DI['user_1'])->revoke_access_from_bases([self::$DI['record_1']->getBaseId()]);
                self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_base(self::$DI['record_1']->getBaseId(), $rightBase);

                self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_sbas(self::$DI['record_1']->get_sbas_id(), $rightSbasReset);
                self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_sbas(self::$DI['record_1']->get_sbas_id(), $rightSbas);

                $client = new Client();

                $webhookWorker = new WebhookWorker(self::$DI['app']);
                $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
                $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

                $payload = [
                    'id'    => $event->getId(),
                    'published' => time()
                ];

                $requestResult = $webhookWorker->deliverEvent($client, [self::$DI['oauth2-app-user1']], $event, $payload);

                $this->assertCount(1, $requestResult);

                /** @var  Request $request */
                $request =  current($requestResult);
                $requestBody = json_decode($request->getBody()->__tostring(), true);
                $this->assertInternalType('array', $requestBody);
                $this->assertArrayHasKey('event', $requestBody);
            }
        }
    }

    public function testNoDeliverWithInvalidRight()
    {
        $this->loadEventsData();
        $eventsTestInvalidUserRight = [
            WebhookEvent::RECORD_CREATED    => [],
            WebhookEvent::RECORD_EDITED     => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADDRECORD],
            WebhookEvent::RECORD_DELETED    => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANADDRECORD],
            WebhookEvent::RECORD_MEDIA_SUBSTITUTED  => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD],
            WebhookEvent::RECORD_COLLECTION_CHANGED => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD, \ACL::CHGSTATUS],
            WebhookEvent::RECORD_STATUS_CHANGED     => [\ACL::ACCESS, \ACL::ACTIF, \ACL::IMGTOOLS],
            WebhookEvent::RECORD_SUBDEF_CREATED     => [\ACL::ACCESS, \ACL::ACTIF, [\ACL::CANMODIFRECORD, \ACL::BAS_CHUPUB]],// only one right required from the sub-array
            WebhookEvent::RECORD_SUBDEF_FAILED      => [\ACL::ACCESS, \ACL::ACTIF, [\ACL::ORDER_MASTER, \ACL::COLL_MANAGE]],
            WebhookEvent::USER_CREATED              => [\ACL::ACCESS, \ACL::ACTIF, \ACL::ORDER_MASTER],
            WebhookEvent::USER_DELETED              => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD],
            WebhookEvent::USER_REGISTRATION_GRANTED => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD],
            WebhookEvent::USER_REGISTRATION_REJECTED=> [\ACL::ACCESS, \ACL::ACTIF, \ACL::CANMODIFRECORD],
            WebhookEvent::NEW_FEED_ENTRY            => [\ACL::ACCESS, \ACL::ACTIF, \ACL::CHGSTATUS],
            WebhookEvent::ORDER_CREATED             => [\ACL::ACCESS, \ACL::ACTIF, \ACL::BAS_CHUPUB],
            WebhookEvent::ORDER_DELIVERED           => [\ACL::ACCESS, \ACL::ACTIF, \ACL::BAS_CHUPUB],
            WebhookEvent::ORDER_DENIED              => [\ACL::ACCESS, \ACL::ACTIF, \ACL::BAS_CHUPUB]
        ];
        $em = self::$DI['app']['orm.em'];

        foreach ($this->events as $type => $tEvent) {
            foreach ($tEvent as $eventName) {
                $event = new WebhookEvent();
                $event
                    ->setName($eventName)
                    ->setType($type)
                    ->setData($this->eventsData[$type])
                    ->setCollectionBaseIds([self::$DI['record_1']->getBaseId()])
                ;

                $em->persist($event);
                $em->flush();

                list($rightSbas, $rightBase) = $this->getUserActiveRights($eventsTestInvalidUserRight, $eventName);

                $rightSbasReset = [
                    \ACL::BAS_MODIFY_STRUCT => false,
                    \ACL::BAS_MODIF_TH      => false,
                    \ACL::BAS_CHUPUB        => false,
                    \ACL::BAS_MANAGE        => false,
                ];

                // re-initialize user right for the test
                self::$DI['app']->getAclForUser(self::$DI['user_1'])->revoke_access_from_bases([self::$DI['record_1']->getBaseId()]);
                if (count($rightBase) > 0) {
                    self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_base(self::$DI['record_1']->getBaseId(), $rightBase);
                }
                self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_sbas(self::$DI['record_1']->get_sbas_id(), $rightSbasReset);
                if (count($rightSbas) > 0) {
                    self::$DI['app']->getAclForUser(self::$DI['user_1'])->update_rights_to_sbas(self::$DI['record_1']->get_sbas_id(), $rightSbas);

                }

                $client = new Client();

                $webhookWorker = new WebhookWorker(self::$DI['app']);
                $webhookWorker->setApplicationBox(self::$DI['app']['phraseanet.appbox']);
                $webhookWorker->setDispatcher(self::$DI['app']['dispatcher']);

                $payload = [
                    'id'    => $event->getId(),
                    'published' => time()
                ];

                $requestResult = $webhookWorker->deliverEvent($client, [self::$DI['oauth2-app-user1']], $event, $payload);

                // Normaly no request send
                $this->assertCount(0, $requestResult);
            }
        }
    }

    private function loadEventsData()
    {
        $em = self::$DI['app']['orm.em'];
        $order = new Order();
        $order
            ->setUser(self::$DI['user_notAdmin'])
            ->setOrderUsage('test')
            ->setDeadline(new \DateTime('+1 day'))
        ;
        $em->persist($order);
        $em->flush();

        $this->eventsData = [
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
                'user_id'  => self::$DI['user_notAdmin']->getId(),
                'granted'  => ['granted'],
                'rejected' => ['rejected']
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
    }

    private function getUserActiveRights(array $eventsRights, $eventName)
    {
        $rightBase = [];
        $rightSbas = [];
        foreach ($eventsRights[$eventName] as $eventRight) {
            if (is_array($eventRight)) {
                foreach ($eventRight as $r) {
                    if ($r == \ACL::ACCESS) {
                        continue;
                    }
                    if (strpos($r, 'bas_') === 0) {
                        $rightSbas[$r] = true;
                    } else {
                        $rightBase[$r] = true;
                    }
                }
            } elseif ($eventRight != \ACL::ACCESS) {  // access is not a real sql column
                if (strpos($eventRight, 'bas_') === 0) {
                    $rightSbas[$eventRight] = true;
                } else {
                    $rightBase[$eventRight] = true;
                }
            }
        }

        return [$rightSbas, $rightBase];
    }
}
