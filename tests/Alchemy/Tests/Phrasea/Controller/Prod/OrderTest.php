<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Notification\Deliverer;
use Symfony\Component\EventDispatcher\Event;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class OrderTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testCreateOrder()
    {
        $app = $this->getApplication();
        $randomValue = $this->setSessionFormToken('prodExportOrder');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_CREATE, function () use (&$triggered) {
            $triggered = true;
        });

        $response = $this->request('POST', '/prod/order/', [
            'lst'      => $this->getRecord1()->getId(),
            'deadline' => '+10 minutes',
            'prodExportOrder_token' => $randomValue
        ]);

        $this->assertTrue($response->isRedirect(), 'Response should be redirect');
        $url = parse_url($response->headers->get('location'));
        $var = [];
        parse_str($url['query'], $var);
        $this->assertTrue(!!$var['success'], 'Response should have a success parameter');
        $this->assertTrue($triggered, 'Creation listener should have been triggered');
    }

    public function testCreateOrderJson()
    {
        $app = $this->getApplication();
        $randomValue = $this->setSessionFormToken('prodExportOrder');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $response = $this->XMLHTTPRequest('POST', '/prod/order/', [
            'lst' => $this->getRecord1()->getId(),
            'deadline' => '+10 minutes',
            'prodExportOrder_token' => $randomValue
        ]);

        $this->assertTrue($response->isOk(), 'Invalid response from create order');
        $this->assertTrue($triggered, 'Order create listener not triggered');
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content), 'content of response should be a valid JSON object');
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertTrue($content->success, 'Success attribute of response content should be true');
    }

    public function testDisplayOrders()
    {
        $this->XMLHTTPRequest('POST', '/prod/order/', [
            'lst' => $this->getRecord1()->getId(),
            'deadline' => '+10 minutes'
        ]);
        $response = $this->request('GET', '/prod/order/', [
            'sort' => 'usage'
        ]);
        $this->assertTrue($response->isOk());
    }

    public function testDisplayOneOrder()
    {
        $order = $this->createOneOrder('I need this pictures');
        $client = $this->getClient();
        $client->request('GET', '/prod/order/' . $order->getId() . '/');
        $this->assertTrue($client->getResponse()->isOk());
    }

    public function testSendOrder()
    {
        $app = $this->getApplication();
        $order = $this->createOneOrder('I need this pictures');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_DELIVER, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $response = $this->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);

        $this->assertTrue($triggered, 'Order delivered listener not triggered');
        $this->assertTrue($response->isRedirect(), 'Could not validate some elements. not a redirect');

        $url = parse_url($response->headers->get('location'));
        parse_str($url['query']);

        $this->assertTrue(strpos($url['query'], 'success=1') === 0, 'Validation of elements is not successful');
    }

    public function testSendOrderJson()
    {
        $app = $this->getApplication();
        $order = $this->createOneOrder('I need this pictures');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_DELIVER, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $parameters = [];

        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }

        $response = $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertTrue($triggered, 'Order delivered listener not triggered');

        $content = json_decode($response->getContent());

        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertTrue( ! ! $content->success, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertObjectHasAttribute('order_id', $content, $response->getContent());
    }

    public function testDenyOrder()
    {
        $app = $this->getApplication();
        $order = $this->createOneOrder('I need this pictures');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_DENY, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $client = $this->getClient();
        $client->request('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);

        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertTrue($triggered, 'Order denied listener not triggered');

        $url = parse_url($client->getResponse()->headers->get('location'));
        $var = [];
        parse_str($url['query'], $var);
        $this->assertTrue( ! ! $var['success']);
    }

    public function testDenyOrderJson()
    {
        $app = $this->getApplication();
        $order = $this->createOneOrder('I need this pictures');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_DENY, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }

        $response = $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);

        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertTrue($triggered, 'Order denied listener not triggered');

        $content = json_decode($response->getContent());

        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertTrue( ! ! $content->success, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertObjectHasAttribute('order_id', $content, $response->getContent());
    }

    public function testTodo()
    {
        $app = $this->getApplication();
        $order = $this->createOneOrder('I need this pictures');

        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_DELIVER, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $this->getClient()->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);

        $this->assertTrue($triggered, 'Order delivered listener not triggered');

        $testOrder = $app['orm.em']->getRepository('Phraseanet:Order')->find($order->getId());

        $this->assertEquals(0, $testOrder->getTodo());
    }

    public function testTodoOnDenied()
    {
        $order = $this->createOneOrder('I need this pictures');
        $orderElement = new OrderElement();
        $record2 = $this->getRecord2();
        $orderElement->setBaseId($record2->getBaseId());
        $orderElement->setRecordId($record2->getRecordId());
        $orderElement->setOrder($order);

        $order->addElement($orderElement);
        $order->setTodo(2);

        $app = $this->getApplication();
        $entityManager = $app['orm.em'];
        $entityManager->persist($order);
        $entityManager->persist($orderElement);
        $entityManager->flush();

        $parameters = [$order->getElements()->first()->getId()];
        $client = $this->getClient();
        $client->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);
        $testOrder = $entityManager->getRepository('Phraseanet:Order')->find($order->getId());
        $this->assertEquals(1, $testOrder->getTodo());

        $parameters = [$orderElement->getId()];
        $client->request('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);

        $testOrder = $entityManager->getRepository('Phraseanet:Order')->find($order->getId());
        $this->assertEquals(0, $testOrder->getTodo());
    }

    private function createOneOrder($usage)
    {
        $app = $this->getApplication();
        $app['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $record1 = $this->getRecord1();

        $order = new Order();
        $order->setOrderUsage($usage);
        $order->setUser(self::$DI['user_alt2']);
        $order->setDeadline(new \DateTime('+10 minutes'));

        $orderElement = new OrderElement();
        $orderElement->setBaseId($record1->getBaseId());
        $orderElement->setRecordId($record1->getRecordId());
        $orderElement->setOrder($order);

        $order->addElement($orderElement);
        $order->setTodo(1);

        $entityManager = $app['orm.em'];
        $entityManager->persist($order);
        $entityManager->persist($orderElement);
        $entityManager->flush();

        return $order;
    }
}
