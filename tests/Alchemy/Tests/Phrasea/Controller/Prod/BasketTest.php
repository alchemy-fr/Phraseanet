<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;

class BasketTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    public function testRootPost()
    {
        self::$DI['record_1'];
        self::$DI['record_2'];
        $route = '/prod/baskets/';

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
            ' ',
            '42',
            self::$DI['record_no_access']->get_serialize_key()
        ];

        $lst = implode(';', $records);

        self::$DI['client']->request(
            'POST', $route, [
            'name' => 'panier',
            'desc' => 'mon beau panier',
            'lst'  => $lst]
        );

        $response = self::$DI['client']->getResponse();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');
        $count = $query->getSingleScalarResult();

        $this->assertEquals(5, $count);
        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$DI['app']['EM']->createQuery('SELECT b FROM Phraseanet:Basket b');
        $result = $query->getResult();

        $basket = array_pop($result);
        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRootPostJSON()
    {
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');
        $count = $query->getSingleScalarResult();

        $route = '/prod/baskets/';

        self::$DI['client']->request(
            'POST'
            , $route
            , [
            'name' => 'panier',
            'desc' => 'mon beau panier',
            ]
            , []
            , [
            "HTTP_ACCEPT" => "application/json"
            ]
        );

        $response = self::$DI['client']->getResponse();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');

        $this->assertEquals($count + 1, $query->getSingleScalarResult());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateGet()
    {
        $route = '/prod/baskets/create/';

        $crawler = self::$DI['client']->request('GET', $route);

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $filter = "form[action='/prod/baskets/']";
        $this->assertEquals(1, $crawler->filter($filter)->count());

        $filter = "form[action='/prod/baskets/'] input[name='name']";
        $this->assertEquals(1, $crawler->filter($filter)->count());

        $filter = "form[action='/prod/baskets/'] textarea[name='description']";
        $this->assertEquals(1, $crawler->filter($filter)->count());
    }

    public function testBasketGet()
    {
        $route = '/prod/baskets/1/';
        self::$DI['client']->request('GET', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketGetAccessDenied()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 3);
        $route = sprintf('/prod/baskets/%s/', $basket->getId());
        self::$DI['client']->request('GET', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testBasketDeleteElementPost()
    {
        $basketElement = self::$DI['app']['EM']->find('Phraseanet:BasketElement', 1);
        $basket = $basketElement->getBasket();

        $this->assertEquals(1, $basket->getElements()->count());

        $route = sprintf(
            "/prod/baskets/%s/delete/%s/", $basket->getId(), $basketElement->getId()
        );

        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(0, $basket->getElements()->count());
    }

    public function testBasketDeldeteElementPostJSON()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $basket_element = self::$DI['app']['EM']->find('Phraseanet:BasketElement', 1);

        $route = sprintf(
            "/prod/baskets/%s/delete/%s/", $basket->getId(), $basket_element->getId()
        );

        self::$DI['client']->request(
            'POST', $route, [], [], [
            "HTTP_ACCEPT" => "application/json"]
        );

        $response = self::$DI['client']->getResponse();
        self::$DI['app']['EM']->refresh($basket);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $basket->getElements()->count());
    }

    public function testBasketDeletePostUnauthorized()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 3);
        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(403, $response->getStatusCode());
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');
        $count = $query->getSingleScalarResult();
        $this->assertEquals(4, $count);
    }

    public function testBasketDeletePost()
    {
        $route = '/prod/baskets/1/delete/';
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');
        $count = $query->getSingleScalarResult();
        $this->assertEquals(3, $count);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketDeletePostJSON()
    {
        $route = '/prod/baskets/1/delete/';
        self::$DI['client']->request('POST', $route, [], [], ["HTTP_ACCEPT" => "application/json"]);
        $response = self::$DI['client']->getResponse();
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');
        $count = $query->getSingleScalarResult();
        $this->assertEquals(3, $count);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketUpdatePost()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = sprintf('/prod/baskets/%s/update/', $basket->getId());

        self::$DI['client']->request(
            'POST', $route, [
            'name'        => 'new_name',
            'description' => 'new_desc']
        );

        $response = self::$DI['client']->getResponse();
        $this->assertEquals('new_name', $basket->getName());
        $this->assertEquals('new_desc', $basket->getDescription());
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketUpdatePostJSON()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = sprintf('/prod/baskets/%s/update/', $basket->getId());

        self::$DI['client']->request(
            'POST', $route, [
            'name'        => 'new_name',
            'description' => 'new_desc'
            ], [], [
            "HTTP_ACCEPT" => "application/json"]
        );

        $response = self::$DI['client']->getResponse();
        $this->assertEquals('new_name', $basket->getName());
        $this->assertEquals('new_desc', $basket->getDescription());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReorderGet()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 4);

        $route = sprintf("/prod/baskets/%s/reorder/", $basket->getId());

        $crawler = self::$DI['client']->request("GET", $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        foreach ($basket->getElements() as $elements) {
            $filter = sprintf("form[action='/prod/baskets/%s/reorder/'] input[name='element[%s]']", $basket->getId(), $elements->getId());
            $this->assertEquals(1, $crawler->filter($filter)->count());
        }
    }

    public function testBasketUpdateGet()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = sprintf('/prod/baskets/%s/update/', $basket->getId());

        $crawler = self::$DI['client']->request(
            'GET', $route, [
            'name'        => 'new_name',
            'description' => 'new_desc']
        );

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $filter = "form[action='/prod/baskets/" . $basket->getId() . "/update/']";
        $this->assertEquals($crawler->filter($filter)->count(), 1);

        $node = $crawler->filter('input[name=name]');
        $this->assertEquals($basket->getName(), $node->attr('value'));
        $node = $crawler->filter('textarea[name=description]');
        $this->assertEquals($basket->getDescription(), $node->text());
    }

    public function testBasketArchivedPost()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = sprintf('/prod/baskets/%s/archive/?archive=1', $basket->getId());
        self::$DI['client']->request('POST', $route);
        $this->assertTrue($basket->getArchived());
        $route = sprintf('/prod/baskets/%s/archive/?archive=0', $basket->getId());
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();
        self::$DI['app']['EM']->refresh($basket);
        $this->assertFalse($basket->getArchived());
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketArchivedPostJSON()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = sprintf('/prod/baskets/%s/archive/?archive=1', $basket->getId());

        self::$DI['client']->request(
            'POST', $route, [], [], [
            "HTTP_ACCEPT" => "application/json"
            ]
        );

        $this->assertTrue($basket->getArchived());

        $route = sprintf('/prod/baskets/%s/archive/?archive=0', $basket->getId());
        self::$DI['client']->request(
            'POST', $route, [], [], [
            "HTTP_ACCEPT" => "application/json"
            ]
        );

        $response = self::$DI['client']->getResponse();
        self::$DI['app']['EM']->refresh($basket);
        $this->assertFalse($basket->getArchived());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAddElementPost()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = sprintf('/prod/baskets/%s/addElements/', $basket->getId());

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
            ' ',
            '42',
            'abhak',
            self::$DI['record_no_access']->get_serialize_key(),
        ];

        $lst = implode(';', $records);

        self::$DI['client']->request('POST', $route, ['lst' => $lst]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertCount(2, $basket->getElements());
    }

    public function testAddElementToValidationPost()
    {
        $datas = self::$DI['app']['EM']->getRepository('Phraseanet:ValidationData')->findAll();
        $countDatas = count($datas);

        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 4);
        $this->assertCount(2, $basket->getElements());
        $route = sprintf('/prod/baskets/%s/addElements/', $basket->getId());

        $records = [
            self::$DI['record_3']->get_serialize_key(),
            self::$DI['record_4']->get_serialize_key(),
            ' ',
            '42',
            'abhak',
            self::$DI['record_no_access']->get_serialize_key(),
        ];

        $lst = implode(';', $records);

        self::$DI['client']->request('POST', $route, ['lst' => $lst]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertCount(4, $basket->getElements());
        $datas = self::$DI['app']['EM']->getRepository('Phraseanet:ValidationData')->findAll();
        $this->assertTrue($countDatas < count($datas), 'assert that ' . count($datas) . ' > ' . $countDatas);
    }

    public function testAddElementPostJSON()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 1);
        $route = '/prod/baskets/1/addElements/';

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        ];

        $lst = implode(';', $records);

        self::$DI['client']->request('POST', $route, ['lst' => $lst], [], ["HTTP_ACCEPT" => "application/json"]);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRouteStealElements()
    {
        $BasketElement = self::$DI['app']['EM']->find('Phraseanet:BasketElement', 1);

        $Basket_1 = $BasketElement->getBasket();
        $Basket_2 = self::$DI['app']['EM']->find('Phraseanet:Basket', 2);

        $route = sprintf('/prod/baskets/%s/stealElements/', $Basket_2->getId());

        self::$DI['client']->request(
            'POST', $route, [
            'elements' => [$BasketElement->getId(), 'ufdsd']
            ], []
        );

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(0, $Basket_1->getElements()->count());
        $this->assertEquals(1, $Basket_2->getElements()->count());
    }

    public function testRouteStealElementsJson()
    {
        $BasketElement = self::$DI['app']['EM']->find('Phraseanet:BasketElement', 1);

        $Basket_1 = $BasketElement->getBasket();

        $Basket_2 = self::$DI['app']['EM']->find('Phraseanet:Basket', 2);

        $route = sprintf('/prod/baskets/%s/stealElements/', $Basket_2->getId());

        self::$DI['client']->request(
            'POST', $route, [
            'elements' => [$BasketElement->getId()]
            ], []
            , [
            "HTTP_ACCEPT" => "application/json"
            ]
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);

        $this->assertEquals(0, $Basket_1->getElements()->count());
        $this->assertEquals(1, $Basket_2->getElements()->count());
    }

    public function testRemoveBasket()
    {
        $basket = self::$DI['app']['EM']->find('Phraseanet:Basket', 4);

        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());
        self::$DI['client']->request('POST', $route, [], [], ["HTTP_ACCEPT" => "application/json"]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(v.id) FROM Phraseanet:ValidationParticipant v');
        $this->assertEquals(0, $query->getSingleScalarResult());

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:BasketElement b');
        $this->assertEquals(1, $query->getSingleScalarResult());

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(v.id) FROM Phraseanet:ValidationSession v');
        $this->assertEquals(0, $query->getSingleScalarResult());

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM Phraseanet:Basket b');
        $this->assertEquals(3, $query->getSingleScalarResult());
    }
}
