<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\Event;
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
        self::$DI['app']['phraseanet.user-query'] = $this->getMockBuilder('\User_Query')->disableOriginalConstructor()->getMock();
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('get_results')->will($this->returnValue(new ArrayCollection([self::$DI['user_alt2']])));
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('on_base_ids')->will($this->returnSelf());
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('who_have_right')->will($this->returnSelf());
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('execute')->will($this->returnSelf());

        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();
        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::ORDER_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });
        self::$DI['client']->request('POST', '/prod/order/', [
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $url = parse_url(self::$DI['client']->getResponse()->headers->get('location'));
        $var = [];
        parse_str($url['query'], $var);
        $this->assertTrue(!!$var['success']);
        $this->assertTrue($triggered);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::createOrder
     */
    public function testCreateOrderJson()
    {
        self::$DI['app']['phraseanet.user-query'] = $this->getMockBuilder('\User_Query')->disableOriginalConstructor()->getMock();
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('get_results')->will($this->returnValue(new ArrayCollection([self::$DI['user_alt2']])));
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('on_base_ids')->will($this->returnSelf());
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('who_have_right')->will($this->returnSelf());
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('execute')->will($this->returnSelf());


        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();
        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::ORDER_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $this->XMLHTTPRequest('POST', '/prod/order/', [
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertTrue($triggered);
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
        $this->mockUserNotificationSettings('eventsmanager_notify_orderdeliver');

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
        $this->mockUserNotificationSettings('eventsmanager_notify_orderdeliver');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);
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
        $this->mockUserNotificationSettings('eventsmanager_notify_ordernotdelivered');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
        $url = parse_url(self::$DI['client']->getResponse()->headers->get('location'));
        $var = [];
        parse_str($url['query'], $var);
        $this->assertTrue( ! ! $var['success']);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::denyOrder
     */
    public function testDenyOrderJson()
    {
        $order = $this->createOneOrder('I need this pictures');

        $this->mockNotificationDeliverer('Alchemy\Phrasea\Notification\Mail\MailInfoOrderCancelled');
        $this->mockUserNotificationSettings('eventsmanager_notify_ordernotdelivered');

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
        $this->mockUserNotificationSettings('eventsmanager_notify_orderdeliver');

        $parameters = [];
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);

        $testOrder = self::$DI['app']['orm.em']->getRepository('Phraseanet:Order')->find($order->getId());
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

        self::$DI['app']['orm.em']->persist($order);
        self::$DI['app']['orm.em']->persist($orderElement);
        self::$DI['app']['orm.em']->flush();

        $parameters = [$order->getElements()->first()->getId()];
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', ['elements' => $parameters]);
        $testOrder = self::$DI['app']['orm.em']->getRepository('Phraseanet:Order')->find($order->getId());
        $this->assertEquals(1, $testOrder->getTodo());

        $parameters = [$orderElement->getId()];
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/deny/', ['elements' => $parameters]);

        $testOrder = self::$DI['app']['orm.em']->getRepository('Phraseanet:Order')->find($order->getId());
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
        $order->setUser(self::$DI['user_alt2']);
        $order->setDeadline(new \DateTime('+10 minutes'));

        $orderElement = new OrderElement();
        $orderElement->setBaseId(self::$DI['record_1']->get_base_id());
        $orderElement->setRecordId(self::$DI['record_1']->get_record_id());
        $orderElement->setOrder($order);

        $order->addElement($orderElement);
        $order->setTodo(1);

        self::$DI['app']['orm.em']->persist($order);
        self::$DI['app']['orm.em']->persist($orderElement);
        self::$DI['app']['orm.em']->flush();

        return $order;
    }
}
