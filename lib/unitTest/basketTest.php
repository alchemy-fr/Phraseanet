<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Doctrine\Common\DataFixtures\Loader;
use PhraseaFixture\Basket as MyFixture;
use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class basketTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected $loader;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
    $this->loader = new Loader();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRootPost()
  {
    $route = '/baskets/';

    $this->client->request('POST', $route, array('name' => 'panier', 'desc' => 'mon beau panier'));

    $response = $this->client->getResponse();

    $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
    );

    $count = $query->getSingleScalarResult();

    $this->assertEquals(1, $count);

    $this->assertEquals(302, $response->getStatusCode());
  }

  public function testCreateGet()
  {
    $route = '/baskets/create/';

    $crawler = $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());

    $this->assertEquals($crawler->filter("form[action='/prod/baskets/']")->count(), 1);
    $this->assertEquals($crawler->filter("form[action='/prod/baskets/'] input[name='name']")->count(), 1);
    $this->assertEquals($crawler->filter("form[action='/prod/baskets/'] textarea[name='description']")->count(), 1);
  }

  public function testBasketGet()
  {
    $basket = $this->insertOneBasket();

    $route = sprintf('/baskets/%s/', $basket->getId());

    $crawler = $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
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

    $crawler = $this->client->request('POST', $route, array(), array(), array("HTTP_ACCEPT" => "application/json"));

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

    $crawler = $this->client->request('POST', $route, array('name' => 'new_name', 'description' => 'new_desc'));

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

    $crawler = $this->client->request('POST', $route, array('name' => 'new_name', 'description' => 'new_desc'), array(), array("HTTP_ACCEPT" => "application/json"));

    $response = $this->client->getResponse();

    $em = self::$core->getEntityManager();
    /* @var $em \Doctrine\ORM\EntityManager */
    $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

    $this->assertEquals('new_name', $basket->getName());
    $this->assertEquals('new_desc', $basket->getDescription());

    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testBasketUpdateGet()
  {
    $basket = $this->insertOneBasket();

    $route = sprintf('/baskets/%s/update/', $basket->getId());

    $crawler = $this->client->request('GET', $route, array('name' => 'new_name', 'description' => 'new_desc'));

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());

    $this->assertEquals($crawler->filter("form[action='/prod/baskets/" . $basket->getId() . "/update/']")->count(), 1);

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

    $em = self::$core->getEntityManager();
    /* @var $em \Doctrine\ORM\EntityManager */
    $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

    $this->assertFalse($basket->getArchived());

    $this->assertEquals(302, $response->getStatusCode());
  }

  public function testBasketArchivedPostJSON()
  {
    $basket = $this->insertOneBasket();

    $route = sprintf('/baskets/%s/archive/', $basket->getId());

    $crawler = $this->client->request('POST', $route, array('archive' => '1'), array(), array("HTTP_ACCEPT" => "application/json"));

    $response = $this->client->getResponse();

    $em = self::$core->getEntityManager();
    /* @var $em \Doctrine\ORM\EntityManager */
    $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

    $this->assertTrue($basket->getArchived());

    $crawler = $this->client->request('POST', $route, array('archive' => '0'), array(), array("HTTP_ACCEPT" => "application/json"));

    $response = $this->client->getResponse();

    $em = self::$core->getEntityManager();
    /* @var $em \Doctrine\ORM\EntityManager */
    $basket = $em->getRepository('Entities\Basket')->find($basket->getId());

    $this->assertFalse($basket->getArchived());

    $this->assertEquals(200, $response->getStatusCode());
  }
  
  public function testAddElementPost()
  {
    $basket = $this->insertOneBasket();

    $route = sprintf('/baskets/%s/addElements/', $basket->getId());

    $records = array(self::$record_1->get_serialize_key(), self::$record_2->get_serialize_key());
    
    $lst = implode(';', $records);
    
    $crawler = $this->client->request('POST', $route, array('lst' => $lst), array(), array("HTTP_ACCEPT" => "application/json"));

    $response = $this->client->getResponse();
    
    $this->assertEquals(302, $response->getStatusCode());
    
    $basket->getElements();
  }

  /**
   *
   * @return \Entities\Basket
   */
  protected function insertOneBasket()
  {
    $basketFixture = new MyFixture\Root(self::$user);

    $this->loader->addFixture($basketFixture);

    $this->insertFixtureInDatabase($this->loader);

    $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(b.id) FROM \Entities\Basket b'
    );

    $count = $query->getSingleScalarResult();

    $this->assertEquals(1, $count);

    return $basketFixture->basket;
  }

}