<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;

class ControllerBasketTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');

        self::giveRightsToUser(self::$DI['app'], self::$DI['user']);
        self::$DI['user']->ACL()->revoke_access_from_bases(array(self::$DI['collection_no_access']->get_base_id()));
        self::$DI['user']->ACL()->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
    }

    public function testRootPost()
    {
        self::$DI['record_1'];
        self::$DI['record_2'];
        $route = '/prod/baskets/';

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
            ' ',
            '42',
            self::$DI['record_no_access']->get_serialize_key()
        );

        $lst = implode(';', $records);

        self::$DI['client']->request(
            'POST', $route, array(
            'name' => 'panier',
            'desc' => 'mon beau panier',
            'lst'  => $lst)
        );

        $response = self::$DI['client']->getResponse();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);

        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$DI['app']['EM']->createQuery('SELECT b FROM \Entities\Basket b');

        $result = $query->getResult();

        $basket = array_shift($result);
        /* @var $basket \Entities\Basket */
        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRootPostJSON()
    {
        $route = '/prod/baskets/';

        self::$DI['client']->request(
            'POST'
            , $route
            , array(
            'name' => 'panier',
            'desc' => 'mon beau panier',
            )
            , array()
            , array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = self::$DI['client']->getResponse();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b');

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

        $crawler = self::$DI['client']->request('GET', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketDeleteElementPost()
    {
        $basket = $this->insertOneBasket();

        $record = self::$DI['record_1'];

        $basket_element = new \Entities\BasketElement();
        $basket_element->setBasket($basket);
        $basket_element->setRecord($record);
        $basket_element->setLastInBasket();

        $basket->addBasketElement($basket_element);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

        $route = sprintf(
            "/prod/baskets/%s/delete/%s/", $basket->getId(), $basket_element->getId()
        );

        $crawler = self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM']->refresh($basket);

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals(0, $basket->getElements()->count());
    }

    public function testBasketDeleteElementPostJSON()
    {
        $basket = $this->insertOneBasket();

        $record = self::$DI['record_1'];

        $basket_element = new \Entities\BasketElement();
        $basket_element->setBasket($basket);
        $basket_element->setRecord($record);
        $basket_element->setLastInBasket();

        $basket->addBasketElement($basket_element);

        self::$DI['app']['EM']->persist($basket);
        self::$DI['app']['EM']->flush();

        $route = sprintf(
            "/prod/baskets/%s/delete/%s/", $basket->getId(), $basket_element->getId()
        );

        $crawler = self::$DI['client']->request(
            'POST', $route, array(), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM']->refresh($basket);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(0, $basket->getElements()->count());
    }

    public function testBasketDeletePost()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());

        $crawler = self::$DI['client']->request('POST', $route);

        $response = self::$DI['client']->getResponse();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketDeletePostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/delete/', $basket->getId());

        $crawler = self::$DI['client']->request(
            'POST', $route, array(), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        self::$DI['client']->getRequest()->setRequestFormat('json');

        $response = self::$DI['client']->getResponse();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketUpdatePost()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/update/', $basket->getId());

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'name'        => 'new_name',
            'description' => 'new_desc')
        );

        $response = self::$DI['client']->getResponse();

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals('new_name', $basket->getName());
        $this->assertEquals('new_desc', $basket->getDescription());

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketUpdatePostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/update/', $basket->getId());

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'name'        => 'new_name',
            'description' => 'new_desc'
            ), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = self::$DI['client']->getResponse();

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

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
            $filter = sprintf("form[action='/prod/baskets/%s/reorder/']", $elements->getId());
            $this->assertEquals(1, $crawler->filter($filter)->count());
        }
    }

    public function testBasketUpdateGet()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/update/', $basket->getId());

        $crawler = self::$DI['client']->request(
            'GET', $route, array(
            'name'        => 'new_name',
            'description' => 'new_desc')
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $filter = "form[action='/prod/baskets/" . $basket->getId() . "/update/']";
        $this->assertEquals($crawler->filter($filter)->count(), 1);

        $node = $crawler
            ->filter('input[name=name]');

        $this->assertEquals($basket->getName(), $node->attr('value'));

        $node = $crawler
            ->filter('textarea[name=description]');

        $this->assertEquals($basket->getDescription(), $node->text());
    }

    public function testBasketArchivedPost()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/archive/', $basket->getId());

        $crawler = self::$DI['client']->request('POST', $route, array('archive' => '1'));

        $response = self::$DI['client']->getResponse();

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertTrue($basket->getArchived());

        $crawler = self::$DI['client']->request('POST', $route, array('archive' => '0'));

        $response = self::$DI['client']->getResponse();

        self::$DI['app']['EM']->refresh($basket);

        $this->assertFalse($basket->getArchived());

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketArchivedPostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/archive/', $basket->getId());

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'archive' => '1'
            ), array(), array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = self::$DI['client']->getResponse();

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertTrue($basket->getArchived());

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'archive' => '0'
            ), array(), array(
            "HTTP_ACCEPT" => "application/json"
            )
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

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
            ' ',
            '42',
            'abhak',
            self::$DI['record_no_access']->get_serialize_key(),
        );

        $lst = implode(';', $records);

        self::$DI['client']->request('POST', $route, array('lst' => $lst));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testAddElementToValidationPost()
    {
        $datas = self::$DI['app']['EM']->getRepository('Entities\ValidationData')->findAll();
        $countDatas = count($datas);

        $basket = $this->insertOneBasket();

        $validationSession = new \Entities\ValidationSession();

        $validationSession->setDescription('Une description au hasard');
        $validationSession->setName('Un nom de validation');

        $expires = new \DateTime();
        $expires->modify('+1 week');

        $validationSession->setExpires($expires);
        $validationSession->setInitiator(self::$DI['user']);

        self::$DI['app']['EM']->persist($validationSession);

        $basket->setValidation($validationSession);

        $validationSession->setBasket($basket);

        $validationParticipant = new \Entities\ValidationParticipant();
        $validationParticipant->setSession($validationSession);
        $validationParticipant->setUser(self::$DI['user_alt1']);

        self::$DI['app']['EM']->persist($validationParticipant);

        $validationSession->addValidationParticipant($validationParticipant);

        self::$DI['app']['EM']->flush();

        $route = sprintf('/prod/baskets/%s/addElements/', $basket->getId());

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key(),
            ' ',
            '42',
            'abhak',
            self::$DI['record_no_access']->get_serialize_key(),
        );

        $lst = implode(';', $records);

        self::$DI['client']->request('POST', $route, array('lst' => $lst));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals(2, $basket->getElements()->count());

        $datas = self::$DI['app']['EM']->getRepository('Entities\ValidationData')->findAll();

        $this->assertTrue($countDatas < count($datas), 'assert that ' . count($datas) . ' > ' . $countDatas);
    }

    public function testAddElementPostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/addElements/', $basket->getId());

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'lst' => $lst
            ), array(), array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = self::$DI['client']->getResponse();

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($basket->getId());

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
            'POST', $route, array(
            'elements' => array($BasketElement->getId(), 'ufdsd')
            ), array()
        );

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($Basket_1->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(0, $basket->getElements()->count());

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($Basket_2->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(1, $basket->getElements()->count());
    }

    public function testRouteStealElementsJson()
    {
        $BasketElement = $this->insertOneBasketElement();

        $Basket_1 = $BasketElement->getBasket();

        $Basket_2 = $this->insertOneBasket();

        $route = sprintf('/prod/baskets/%s/stealElements/', $Basket_2->getId());

        self::$DI['client']->request(
            'POST', $route, array(
            'elements' => array($BasketElement->getId())
            ), array()
            , array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($Basket_1->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(0, $basket->getElements()->count());

        $basket = self::$DI['app']['EM']->getRepository('Entities\Basket')->find($Basket_2->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(1, $basket->getElements()->count());
    }

    /**
     * Test when i remove a basket, all relations are removed too :
     * - basket elements
     * - validations sessions
     * - validation participants
     */
    public function testRemoveBasket()
    {
        $basket = $this->insertOneBasketEnv();

        $basket = self::$DI['app']['EM']->find("Entities\Basket", $basket->getId());

        self::$DI['app']['EM']->remove($basket);
        self::$DI['app']['EM']->flush();

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(v.id) FROM \Entities\ValidationParticipant v');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $query = self::$DI['app']['EM']->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\BasketElement b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(v.id) FROM \Entities\ValidationSession v');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $query = self::$DI['app']['EM']->createQuery('SELECT COUNT(b.id) FROM \Entities\Basket b');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);
    }
}
