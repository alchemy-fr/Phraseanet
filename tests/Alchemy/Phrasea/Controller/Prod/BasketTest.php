<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

class ControllerBasketTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
    }

    public function testRootPost()
    {
        static::$records['record_1'];
        static::$records['record_2'];
        $route = '/baskets/';

        $records = array(
            static::$records['record_1']->get_serialize_key(),
            static::$records['record_2']->get_serialize_key(),
            ' ',
            '42',
            static::$records['record_no_access']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $this->client->request(
            'POST', $route, array(
            'name' => 'panier',
            'desc' => 'mon beau panier',
            'lst'  => $lst)
        );

        $response = $this->client->getResponse();

        $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);

        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$core->getEntityManager()->createQuery(
            'SELECT b FROM \Entities\Basket b'
        );


        $basket = array_shift($query->getResult());
        /* @var $basket \Entities\Basket */
        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRootPostJSON()
    {
        $route = '/baskets/';

        $this->client->request(
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

        $response = $this->client->getResponse();

        $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateGet()
    {
        $route = '/baskets/create/';

        $crawler = $this->client->request('GET', $route);

        $response = $this->client->getResponse();

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

        $route = sprintf('/baskets/%s/', $basket->getId());

        $crawler = $this->client->request('GET', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketDeleteElementPost()
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = self::$core->getEntityManager();

        $basket = $this->insertOneBasket();

        $record = static::$records['record_1'];

        $basket_element = new \Entities\BasketElement();
        $basket_element->setBasket($basket);
        $basket_element->setRecord($record);
        $basket_element->setLastInBasket();

        $basket->addBasketElement($basket_element);

        $em->persist($basket);

        $em->flush();

        $route = sprintf(
            "/baskets/%s/delete/%s/", $basket->getId(), $basket_element->getId()
        );

        $crawler = $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $em->refresh($basket);

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals(0, $basket->getElements()->count());
    }

    public function testBasketDeleteElementPostJSON()
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = self::$core->getEntityManager();

        $basket = $this->insertOneBasket();

        $record = static::$records['record_1'];

        $basket_element = new \Entities\BasketElement();
        $basket_element->setBasket($basket);
        $basket_element->setRecord($record);
        $basket_element->setLastInBasket();

        $basket->addBasketElement($basket_element);

        $em->persist($basket);

        $em->flush();

        $route = sprintf(
            "/baskets/%s/delete/%s/", $basket->getId(), $basket_element->getId()
        );

        $crawler = $this->client->request(
            'POST', $route, array(), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = $this->client->getResponse();

        $em->refresh($basket);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(0, $basket->getElements()->count());
    }

    public function testBasketDeletePost()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/delete/', $basket->getId());

        $crawler = $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketDeletePostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/delete/', $basket->getId());

        $crawler = $this->client->request(
            'POST', $route, array(), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $this->client->getRequest()->setRequestFormat('json');

        $response = $this->client->getResponse();

        $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBasketUpdatePost()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/update/', $basket->getId());

        $crawler = $this->client->request(
            'POST', $route, array(
            'name'        => 'new_name',
            'description' => 'new_desc')
        );

        $response = $this->client->getResponse();

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals('new_name', $basket->getName());
        $this->assertEquals('new_desc', $basket->getDescription());

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketUpdatePostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/update/', $basket->getId());

        $crawler = $this->client->request(
            'POST', $route, array(
            'name'        => 'new_name',
            'description' => 'new_desc'
            ), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = $this->client->getResponse();

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals('new_name', $basket->getName());
        $this->assertEquals('new_desc', $basket->getDescription());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReorderGet()
    {
        $basket = $this->insertOneBasketEnv();

        $route = sprintf("/baskets/%s/reorder/", $basket->getId());

        $crawler = $this->client->request("GET", $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($basket->getElements() as $elements) {
            $filter = sprintf("form[action='/prod/baskets/%s/reorder/']", $elements->getId());
            $this->assertEquals(1, $crawler->filter($filter)->count());
        }
    }

    public function testBasketUpdateGet()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/update/', $basket->getId());

        $crawler = $this->client->request(
            'GET', $route, array(
            'name'        => 'new_name',
            'description' => 'new_desc')
        );

        $response = $this->client->getResponse();

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

        $route = sprintf('/baskets/%s/archive/', $basket->getId());

        $crawler = $this->client->request('POST', $route, array('archive' => '1'));

        $response = $this->client->getResponse();

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertTrue($basket->getArchived());

        $crawler = $this->client->request('POST', $route, array('archive' => '0'));

        $response = $this->client->getResponse();

        $em->refresh($basket);

        $this->assertFalse($basket->getArchived());

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testBasketArchivedPostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/archive/', $basket->getId());

        $crawler = $this->client->request(
            'POST', $route, array(
            'archive' => '1'
            ), array(), array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = $this->client->getResponse();

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertTrue($basket->getArchived());

        $crawler = $this->client->request(
            'POST', $route, array(
            'archive' => '0'
            ), array(), array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = $this->client->getResponse();

        $em->refresh($basket);

        $this->assertFalse($basket->getArchived());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAddElementPost()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/addElements/', $basket->getId());

        $records = array(
            static::$records['record_1']->get_serialize_key(),
            static::$records['record_2']->get_serialize_key(),
            ' ',
            '42',
            'abhak',
            static::$records['record_no_access']->get_serialize_key(),
        );

        $lst = implode(';', $records);

        $this->client->request('POST', $route, array('lst' => $lst));

        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testAddElementToValidationPost()
    {

        $em = self::$core->getEntityManager();

        $datas = $em->getRepository('Entities\ValidationData')->findAll();
        $countDatas = count($datas);

        $basket = $this->insertOneBasket();

        $validationSession = new \Entities\ValidationSession();

        $validationSession->setDescription('Une description au hasard');
        $validationSession->setName('Un nom de validation');

        $expires = new \DateTime();
        $expires->modify('+1 week');

        $validationSession->setExpires($expires);
        $validationSession->setInitiator(self::$user);

        $em->persist($validationSession);

        $basket->setValidation($validationSession);

        $validationSession->setBasket($basket);

        $validationParticipant = new \Entities\ValidationParticipant();
        $validationParticipant->setSession($validationSession);
        $validationParticipant->setUser(self::$user_alt1);

        $em->persist($validationParticipant);

        $validationSession->addValidationParticipant($validationParticipant);

        $em->flush();

        $route = sprintf('/baskets/%s/addElements/', $basket->getId());

        $records = array(
            static::$records['record_1']->get_serialize_key(),
            static::$records['record_2']->get_serialize_key(),
            ' ',
            '42',
            'abhak',
            static::$records['record_no_access']->get_serialize_key(),
        );

        $lst = implode(';', $records);

        $this->client->request('POST', $route, array('lst' => $lst));

        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals(2, $basket->getElements()->count());

        $datas = $em->getRepository('Entities\ValidationData')->findAll();

        $this->assertTrue($countDatas < count($datas), 'assert that ' . count($datas) . ' > ' . $countDatas);
    }

    public function testAddElementPostJSON()
    {
        $basket = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/addElements/', $basket->getId());

        $records = array(
            static::$records['record_1']->get_serialize_key(),
            static::$records['record_2']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = $this->client->request(
            'POST', $route, array(
            'lst' => $lst
            ), array(), array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = $this->client->getResponse();

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(2, $basket->getElements()->count());
    }

    public function testRouteStealElements()
    {
        $em = self::$core->getEntityManager();

        $BasketElement = $this->insertOneBasketElement();

        $Basket_1 = $BasketElement->getBasket();

        $Basket_2 = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/stealElements/', $Basket_2->getId());

        $this->client->request(
            'POST', $route, array(
            'elements' => array($BasketElement->getId(), 'ufdsd')
            ), array()
        );

        $response = $this->client->getResponse();

        $this->assertTrue($response->isRedirect());

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $basket = $em->getRepository('Entities\Basket')->find($Basket_1->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(0, $basket->getElements()->count());

        $basket = $em->getRepository('Entities\Basket')->find($Basket_2->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(1, $basket->getElements()->count());
    }

    public function testRouteStealElementsJson()
    {
        $em = self::$core->getEntityManager();

        $BasketElement = $this->insertOneBasketElement();

        $Basket_1 = $BasketElement->getBasket();

        $Basket_2 = $this->insertOneBasket();

        $route = sprintf('/baskets/%s/stealElements/', $Basket_2->getId());

        $this->client->request(
            'POST', $route, array(
            'elements' => array($BasketElement->getId())
            ), array()
            , array(
            "HTTP_ACCEPT" => "application/json"
            )
        );

        $response = $this->client->getResponse();


        $this->assertEquals(200, $response->getStatusCode());

        $datas = (array) json_decode($response->getContent());

        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);

        $basket = $em->getRepository('Entities\Basket')->find($Basket_1->getId());
        $this->assertInstanceOf('\Entities\Basket', $basket);
        $this->assertEquals(0, $basket->getElements()->count());

        $basket = $em->getRepository('Entities\Basket')->find($Basket_2->getId());
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

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $basket = $em->find("Entities\Basket", $basket->getId());

        $em->remove($basket);

        $em->flush();

        $query = $em->createQuery(
            'SELECT COUNT(v.id) FROM \Entities\ValidationParticipant v'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $query = $em->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\BasketElement b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        $query = $em->createQuery(
            'SELECT COUNT(v.id) FROM \Entities\ValidationSession v'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);


        $query = $em->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);
    }
}
