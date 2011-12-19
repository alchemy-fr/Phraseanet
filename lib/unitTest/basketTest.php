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

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
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

    
//    $form = $crawler->selectButton(_('boutton::valider'))->form();
//    $crawler = $this->client->submit($form, array('name' => 'Hey you!', 'description' => 'Hey there!'));
    
  }
  
  public function testBasketGet()
  {
    $basketFixture = new MyFixture\Root(self::$user);
    
    $this->loader->addFixture($basketFixture);
    
    $this->insertFixtureInDatabase($this->loader);
    
    $route = sprintf('/baskets/%s/', $basketFixture->basketId);
    
    $crawler = $this->client->request('GET', $route);
    
    $response = $this->client->getResponse();
    
    $this->assertEquals(200, $response->getStatusCode());
    
  }
}