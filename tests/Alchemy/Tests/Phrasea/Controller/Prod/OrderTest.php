<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Doctrine\Common\Collections\ArrayCollection;
use Entities\Order;
use Entities\OrderElement;

class OrderTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
        self::$DI['client']->request('POST', '/prod/order/', array(
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::createOrder
     */
    public function testCreateOrderJson()
    {
        $this->XMLHTTPRequest('POST', '/prod/order/', array(
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::displayOrders
     */
    public function testDisplayOrders()
    {
        self::$DI['client']->request('GET', '/prod/order/');
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

        $parameters = array();
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', array('elements' => $parameters));
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

        $parameters = array();
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/send/', array('elements' => $parameters));
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

        $parameters = array();
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/deny/', array('elements' => $parameters));
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

        $parameters = array();
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        $this->XMLHTTPRequest('POST', '/prod/order/' . $order->getId() . '/deny/', array('elements' => $parameters));
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

        $parameters = array();
        foreach ($order->getElements() as $element) {
            $parameters[] = $element->getId();
        }
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', array('elements' => $parameters));

        $testOrder = self::$DI['app']['EM']->getRepository('Entities\Order')->find($order->getId());
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

        $parameters = array($order->getElements()->first()->getId());
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/send/', array('elements' => $parameters));
        $testOrder = self::$DI['app']['EM']->getRepository('Entities\Order')->find($order->getId());
        $this->assertEquals(1, $testOrder->getTodo());

        $parameters = array($orderElement->getId());
        self::$DI['client']->request('POST', '/prod/order/' . $order->getId() . '/deny/', array('elements' => $parameters));

        $testOrder = self::$DI['app']['EM']->getRepository('Entities\Order')->find($order->getId());
        $this->assertEquals(0, $testOrder->getTodo());
    }

    private function createOneOrder($usage)
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        $receveid = array(self::$DI['record_1']->get_serialize_key() => self::$DI['record_1']);

        $order = new Order();
        $order->setOrderUsage($usage);
        $order->setUsrId(self::$DI['user_alt2']->get_id());
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
