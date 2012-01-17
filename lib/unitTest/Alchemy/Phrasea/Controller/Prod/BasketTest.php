<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ControllerBasketTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = 2;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRootPost()
  {
    $route = '/baskets/';

    $records = array(
        self::$record_1->get_serialize_key(),
        self::$record_2->get_serialize_key(),
        ' ',
        '42',
        self::$record_no_access->get_serialize_key()
    );
    
    $lst = implode(';', $records);

    $this->client->request(
            'POST', $route, array(
        'name' => 'panier',
        'desc' => 'mon beau panier',
        'lst' => $lst)
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

    $record = self::$record_1;

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

    $record = self::$record_1;

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
        'name' => 'new_name',
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
        'name' => 'new_name',
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

    foreach ($basket->getElements() as $elements)
    {
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
        'name' => 'new_name',
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
        self::$record_1->get_serialize_key(),
        self::$record_2->get_serialize_key(),
        ' ',
        '42',
        'abhak',
        self::$record_no_access->get_serialize_key(),
    );

    $lst = implode(';', $records);

    $crawler = $this->client->request('POST', $route, array('lst' => $lst));

    $response = $this->client->getResponse();

    $this->assertEquals(302, $response->getStatusCode());

    $em = self::$core->getEntityManager();
    /* @var $em \Doctrine\ORM\EntityManager */
    $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

    $this->assertEquals(2, $basket->getElements()->count());
  }

  public function testAddElementPostJSON()
  {
    $basket = $this->insertOneBasket();

    $route = sprintf('/baskets/%s/addElements/', $basket->getId());

    $records = array(
        self::$record_1->get_serialize_key(),
        self::$record_2->get_serialize_key()
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
    
    $crawler = $this->client->request(
            'POST', $route, array(
        'elements' => array($BasketElement->getId(), 'ufdsd')
            ), array()
    );

    $response = $this->client->getResponse();
    
    
    $this->assertEquals(302, $response->getStatusCode());

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
    
    $crawler = $this->client->request(
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