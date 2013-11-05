<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;

class OrderTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::createOrder
     * @covers Alchemy\Phrasea\Controller\Prod\Order::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Order::call
     */
    public function testCreateOrder()
    {
        $eventManagerStub = $this->getMockBuilder('\eventsmanager_broker')
                     ->disableOriginalConstructor()
                     ->getMock();

        $eventManagerStub->expects($this->once())
             ->method('trigger')
             ->with($this->equalTo('__NEW_ORDER__'), $this->isType('array'))
             ->will($this->returnValue(null));

        self::$DI['app']['events-manager'] = $eventManagerStub;
        self::$DI['client']->request('POST', '/prod/order/', [
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::createOrder
     */
    public function testCreateOrderJson()
    {
        $eventManagerStub = $this->getMockBuilder('\eventsmanager_broker')
                     ->disableOriginalConstructor()
                     ->getMock();

        $eventManagerStub->expects($this->once())
             ->method('trigger')
             ->with($this->equalTo('__NEW_ORDER__'), $this->isType('array'))
             ->will($this->returnValue(null));

        self::$DI['app']['events-manager'] = $eventManagerStub;

        $this->XMLHTTPRequest('POST', '/prod/order/', [
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertTrue($content->success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::displayOrders
     */
    public function testDisplayOrders()
    {
        $this->XMLHTTPRequest('POST', '/prod/order/', [
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ]);
        self::$DI['client']->request('GET', '/prod/order/', [
            'sort' => 'usage'
        ]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::displayOneOrder
     */
    public function testDisplayOneOrder()
    {
        $order = $this->createOneOrder('I need this pictures');
        self::$DI['client']->request('GET', '/prod/order/' . $order->getId() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::sendOrder
     */
    public function testSendOrder()
    {
        $order = $this->createOneOrder('I need this pictures');

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $url = parse_url(self::$DI['client']->getResponse()->headers->get('location'));
        parse_str($url['query']);
        $this->assertTrue( strpos($url['query'], 'success=1') === 0);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::sendOrder
     */
    public function testSendOrderJson()
    {
        $order = $this->createOneOrder('I need this pictures');

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertTrue( ! ! $content->success, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertObjectHasAttribute('order_id', $content, $response->getContent());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::denyOrder
     */
    public function testDenyOrder()
    {
        $order = $this->createOneOrder('I need this pictures');

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $url = parse_url(self::$DI['client']->getResponse()->headers->get('location'));
        parse_str($url['query']);
        $this->assertTrue( ! ! $success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::denyOrder
     */
    public function testDenyOrderJson()
    {
        $order = $this->createOneOrder('I need this pictures');

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertTrue( ! ! $content->success, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertObjectHasAttribute('order_id', $content, $response->getContent());
    }

    public function testTodo()
    {
        $order = $this->createOneOrder('I need this pictures');

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoOrderDelivered');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);

        $testOrder = self::$DI['app']['EM']->getRepository('Phraseanet:Order')->find($order->getId());
        $this->assertEquals(0, $testOrder->getTodo());
    }

    public function testTodoOnDenied()
    {
        $order = $this->createOneOrder('I need this pictures');
        $orderElement = new OrderElement();
        $orderElement->setBaseId(self::$DI['record_2']->get_base_id());
        $orderElement->setRecordId(self::$DI['record_2']->get_record_id());
        $orderElement->setOrder($order);

        $order->addElement($orderElement);
        $order->setTodo(2);

        self::$DI['app']['EM']->persist($order);
        self::$DI['app']['EM']->persist($orderElement);
        self::$DI['app']['EM']->flush();

        $parameters = [$order->getElements()->first()->getId()];
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);
        $testOrder = self::$DI['app']['EM']->getRepository('Phraseanet:Order')->find($order->getId());
        $this->assertEquals(1, $testOrder->getTodo());

        $parameters = [$orderElement->getId()];
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);

        $testOrder = self::$DI['app']['EM']->getRepository('Phraseanet:Order')->find($order->getId());
        $this->assertEquals(0, $testOrder->getTodo());
    }

    private function createOneOrder($usage)
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $receveid = [self::$DI['record_1']->get_serialize_key() => self::$DI['record_1']];

        $order = new Order();
        $order->setOrderUsage($usage);
        $order->setUsrId(self::$DI['user_alt2']->getId());
        $order->setDeadline(new \DateTime('+10 minutes'));

        $orderElement = new OrderElement();
        $orderElement->setBaseId(self::$DI['record_1']->get_base_id());
        $orderElement->setRecordId(self::$DI['record_1']->get_record_id());
        $orderElement->setOrder($order);

        $order->addElement($orderElement);
        $order->setTodo(1);

        self::$DI['app']['EM']->persist($order);
        self::$DI['app']['EM']->persist($orderElement);
        self::$DI['app']['EM']->flush();

        return $order;
    }
}
