<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;

class ControllerBasketTest extends \PhraseanetAuthenticatedWebTestCase
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

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\Basket b');
        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);
        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$DI['app']['EM']->createQuery('SELECT b FROM \Alchemy\Phrasea\Model\Entities\Basket b');
        $result = $query->getResult();

        $basket = array_shift($result);
        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRootPostJSON()
    {
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

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\Basket b');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);

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
        $basket = $this->insertOneBasket();
        $route = sprintf('/prod/baskets/%s/', $basket->getId());
        self::$DI['client']->request('GET', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketGetAccessDenied()
    {
        $basket = $this->insertOneBasket(self::$DI['user_alt1']);
        $route = sprintf('/prod/baskets/%s/', $basket->getId());
        self::$DI['client']->request('GET', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testBasketDeleteElementPost()
    {
        $basketElement = $this->insertOneBasketElement();
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
        $basket = $this->insertOneBasket();

        $record = self::$DI['record_1'];

        $basket_element = new \Alchemy\Phrasea\Model\Entities\BasketElement();
        $basket_element->setBasket($basket);
        $basket_element->setRecord($record);
        $basket_element->setLastInBasket();

        $basket->addElement($basket_element);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

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
        $basket = $this->insertOneBasket(self::$DI['user_alt1']);
        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(403, $response->getStatusCode());
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\Basket b');
        $count = $query->getSingleScalarResult();
        $this->assertEquals(1, $count);
    }

    public function testBasketDeletePost()
    {
        $basket = $this->insertOneBasket();
        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\Basket b');
        $count = $query->getSingleScalarResult();
        $this->assertEquals(0, $count);
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketDeletePostJSON()
    {
        $basket = $this->insertOneBasket();
        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());
        self::$DI['client']->request('POST', $route, [], [], ["HTTP_ACCEPT" => "application/json"]);
        $response = self::$DI['client']->getResponse();
        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\Basket b');
        $count = $query->getSingleScalarResult();
        $this->assertEquals(0, $count);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketUpdatePost()
    {
        $basket = $this->insertOneBasket();

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
        $basket = $this->insertOneBasket();
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
        $basket = $this->insertOneBasketEnv();

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
        $basket = $this->insertOneBasket();

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
        $basket = $this->insertOneBasket();
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
        $basket = $this->insertOneBasket();

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
        $basket = $this->insertOneBasket();

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
        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testAddElementToValidationPost()
    {
        $datas = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\ValidationData')->findAll();
        $countDatas = count($datas);

        $basket = $this->insertOneBasket();

        $validationSession = new \Alchemy\Phrasea\Model\Entities\ValidationSession();

        $expires = new \DateTime();
        $expires->modify('+1 week');

        $validationSession->setExpires($expires);
        $validationSession->setInitiator(self::$DI['user']);

        self::$DI['app']['EM']->persist($validationSession);

        $basket->setValidation($validationSession);

        $validationSession->setBasket($basket);

        $validationParticipant = new \Alchemy\Phrasea\Model\Entities\ValidationParticipant();
        $validationParticipant->setSession($validationSession);
        $validationParticipant->setUser(self::$DI['user_alt1']);

        self::$DI['app']['EM']->persist($validationParticipant);

        $validationSession->addParticipant($validationParticipant);

        self::$DI['app']['EM']->flush();

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
        $this->assertEquals(2, $basket->getElements()->count());
        $datas = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\ValidationData')->findAll();
        $this->assertTrue($countDatas < count($datas), 'assert that ' . count($datas) . ' > ' . $countDatas);
    }

    public function testAddElementPostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/addElements/', $basket->getId());

        $records = [
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        ];

        $lst = implode(';', $records);

        self::$DI['client']->request(
            'POST', $route, [
            'lst' => $lst
            ], [], [
            "HTTP_ACCEPT" => "application/json"
            ]
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRouteStealElements()
    {
        $BasketElement = $this->insertOneBasketElement();

        $Basket_1 = $BasketElement->getBasket();

        $Basket_2 = $this->insertOneBasket();

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
        $BasketElement = $this->insertOneBasketElement();

        $Basket_1 = $BasketElement->getBasket();

        $Basket_2 = $this->insertOneBasket();

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
        $basket = $this->insertOneBasketEnv();

        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());
        self::$DI['client']->request('POST', $route, [], [], ["HTTP_ACCEPT" => "application/json"]);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(v.id) FROM \Alchemy\Phrasea\Model\Entities\ValidationParticipant v');
        $this->assertEquals(0, $query->getSingleScalarResult());

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\BasketElement b');
        $this->assertEquals(0, $query->getSingleScalarResult());

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(v.id) FROM \Alchemy\Phrasea\Model\Entities\ValidationSession v');
        $this->assertEquals(0, $query->getSingleScalarResult());

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Alchemy\Phrasea\Model\Entities\Basket b');
        $this->assertEquals(0, $query->getSingleScalarResult());
    }
}
