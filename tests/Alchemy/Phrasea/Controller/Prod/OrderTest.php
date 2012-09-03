<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/Prod/Order.php';


class OrderTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     *
     * @return Client A Client instance
     */
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::createOrder
     * @covers Alchemy\Phrasea\Controller\Prod\Order::connect
     * @covers Alchemy\Phrasea\Controller\Prod\Order::call
     */
    public function testCreateOrder()
    {
        $this->client->request('POST', '/order/', array(
            'lst' => static::$records['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ));

        $this->assertTrue($this->client->getResponse()->isRedirect());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::createOrder
     */
    public function testCreateOrderJson()
    {
        $this->XMLHTTPRequest('POST', '/order/', array(
            'lst' => static::$records['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ));

        $response = $this->client->getResponse();
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
        $this->client->request('GET', '/order/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::displayOneOrder
     */
    public function testDisplayOneOrder()
    {
        $order = $this->createOneOrder('I need this pictures');
        $this->client->request('GET', '/order/' . $order->get_order_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::sendOrder
     */
    public function testSendOrder()
    {
        $order = $this->createOneOrder('I need this pictures');
        $parameters = array();
        foreach($order as $id => $element) {
            $parameters[] = $id;
        }
        $this->client->request('POST', '/order/' . $order->get_order_id() . '/send/', $parameters);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $url = parse_url($this->client->getResponse()->headers->get('location'));
        parse_str($url['query']);
        $this->assertTrue( ! ! $success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::sendOrder
     */
    public function testSendOrderJson()
    {
        $order = $this->createOneOrder('I need this pictures');
        $parameters = array();
        foreach($order as $id => $element) {
            $parameters[] = $id;
        }
        $this->XMLHTTPRequest('POST', '/order/' . $order->get_order_id() . '/send/',$parameters);
        $this->assertTrue($this->client->getResponse()->isOk());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertTrue(! ! $content->success, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertObjectHasAttribute('order_id', $content, $response->getContent());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::denyOrder
     */
    public function testDenyOrder()
    {
        $order = $this->createOneOrder('I need this pictures');
        $parameters = array();
        foreach($order as $id => $element) {
            $parameters[] = $id;
        }
        $this->client->request('POST', '/order/' . $order->get_order_id() . '/deny/', $parameters);
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $url = parse_url($this->client->getResponse()->headers->get('location'));
        parse_str($url['query']);
        $this->assertTrue( ! ! $success);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\Order::denyOrder
     */
    public function testDenyOrderJson()
    {
        $order = $this->createOneOrder('I need this pictures');
        $parameters = array();
        foreach($order as $id => $element) {
            $parameters[] = $id;
        }
        $this->XMLHTTPRequest('POST', '/order/' . $order->get_order_id() . '/deny/',$parameters);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('success', $content, $response->getContent());
        $this->assertTrue( ! ! $content->success, $response->getContent());
        $this->assertObjectHasAttribute('msg', $content, $response->getContent());
        $this->assertObjectHasAttribute('order_id', $content, $response->getContent());
    }

    private function createOneOrder($usage)
    {
        $exportOrder = new \set_exportorder(static::$records['record_1']->get_serialize_key(), '');
        $orderId = $exportOrder->order_available_elements(
                    self::$user_alt2->get_id(), $usage, '+10 minutes'
            );

        return new \set_order($orderId);
    }
}
