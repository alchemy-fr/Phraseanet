<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../Alchemy/Phrasea/Application/Lightbox.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationLightboxTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected $feed;
  protected $entry;
  protected $item;
  protected $validation_basket;
  protected static $need_records = 1;

//  protected static $need_subdefs = true;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
    $appbox = appbox::get_instance();
    $this->feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');
    $publisher = array_shift($this->feed->get_publishers());
    $this->entry = Feed_Entry_Adapter::create($appbox, $this->feed, $publisher, 'title', "sub Titkle", " jean pierre", "jp@test.com");
    $this->item = Feed_Entry_Item::create($appbox, $this->entry, self::$record_1);
  }

  public function tearDown()
  {
    if($this->feed instanceof Feed_Adapter)
      $this->feed->delete();
    parent::tearDown();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../Alchemy/Phrasea/Application/Lightbox.php';
  }

  public function testRouteSlash()
  {
    $baskets = $this->insertFiveBasket();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
    $this->assertEquals(5, $crawler->filter('div.basket_wrapper')->count());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
    $this->assertEquals($crawler->filter('div.basket_wrapper')->count(), count($baskets));

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testAjaxNoteForm()
  {
    $basket = $this->insertOneValidationBasket();
    $basket_element = $basket->getELements()->first();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('', trim($this->client->getResponse()->getContent()));

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('', trim($this->client->getResponse()->getContent()));

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/ajax/NOTE_FORM/' . $basket_element->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertNotEquals('', trim($this->client->getResponse()->getContent()));
  }

  public function testAjaxElement()
  {
    $basket_element = $this->insertOneBasketElement();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
    $datas = json_decode($this->client->getResponse()->getContent());

    $this->assertObjectHasAttribute('number', $datas);
    $this->assertObjectHasAttribute('title', $datas);
    $this->assertObjectHasAttribute('preview', $datas);
    $this->assertObjectHasAttribute('options_html', $datas);
    $this->assertObjectHasAttribute('agreement_html', $datas);
    $this->assertObjectHasAttribute('selector_html', $datas);
    $this->assertObjectHasAttribute('note_html', $datas);
    $this->assertObjectHasAttribute('caption', $datas);

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
    $datas = json_decode($this->client->getResponse()->getContent());

    $this->assertObjectHasAttribute('number', $datas);
    $this->assertObjectHasAttribute('title', $datas);
    $this->assertObjectHasAttribute('preview', $datas);
    $this->assertObjectHasAttribute('options_html', $datas);
    $this->assertObjectHasAttribute('agreement_html', $datas);
    $this->assertObjectHasAttribute('selector_html', $datas);
    $this->assertObjectHasAttribute('note_html', $datas);
    $this->assertObjectHasAttribute('caption', $datas);

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/ajax/LOAD_BASKET_ELEMENT/' . $basket_element->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertNotEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
  }

  public function testAjaxFeedItem()
  {
    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/ajax/LOAD_FEED_ITEM/' . $this->entry->get_id() . '/' . $this->item->get_id() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
    $datas = json_decode($this->client->getResponse()->getContent());

    $this->assertObjectHasAttribute('number', $datas);
    $this->assertObjectHasAttribute('title', $datas);
    $this->assertObjectHasAttribute('preview', $datas);
    $this->assertObjectHasAttribute('options_html', $datas);
    $this->assertObjectHasAttribute('agreement_html', $datas);
    $this->assertObjectHasAttribute('selector_html', $datas);
    $this->assertObjectHasAttribute('note_html', $datas);
    $this->assertObjectHasAttribute('caption', $datas);

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/ajax/LOAD_FEED_ITEM/' . $this->entry->get_id() . '/' . $this->item->get_id() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
    $datas = json_decode($this->client->getResponse()->getContent());

    $this->assertObjectHasAttribute('number', $datas);
    $this->assertObjectHasAttribute('title', $datas);
    $this->assertObjectHasAttribute('preview', $datas);
    $this->assertObjectHasAttribute('options_html', $datas);
    $this->assertObjectHasAttribute('agreement_html', $datas);
    $this->assertObjectHasAttribute('selector_html', $datas);
    $this->assertObjectHasAttribute('note_html', $datas);
    $this->assertObjectHasAttribute('caption', $datas);

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/ajax/LOAD_FEED_ITEM/' . $this->entry->get_id() . '/' . $this->item->get_id() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertNotEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
  }

  public function testValidate()
  {

    $basket = $this->insertOneValidationBasket();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/validate/' . $basket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/validate/' . $basket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/validate/' . $basket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testCompare()
  {
    $basket = $this->insertOneBasket();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/compare/' . $basket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/compare/' . $basket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/compare/' . $basket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testFeedEntry()
  {
    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/feeds/entry/' . $this->entry->get_id() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/feeds/entry/' . $this->entry->get_id() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/feeds/entry/' . $this->entry->get_id() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testAjaxReport()
  {
    $validationBasket = $this->insertOneValidationBasket();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);
    $crawler = $this->client->request('GET', '/ajax/LOAD_REPORT/' . $validationBasket->getId() . '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testAjaxSetNote()
  {
    $validationBasket = $this->insertOneValidationBasket();
    $validationBasketElement = $validationBasket->getElements()->first();

    $crawler = $this->client->request('POST', '/ajax/SET_NOTE/' . $validationBasketElement->getId() . '/');
    $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

    $crawler = $this->client->request(
            'POST'
            , '/ajax/SET_NOTE/' . $validationBasketElement->getId() . '/'
            , array('note' => 'une jolie note')
    );

    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));

    $datas = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($datas), 'asserting good json datas');
    $this->assertObjectHasAttribute('datas', $datas);
    $this->assertObjectHasAttribute('error', $datas);
  }

  public function testAjaxSetAgreement()
  {
    $validationBasket = $this->insertOneValidationBasket();
    $validationBasketElement = $validationBasket->getElements()->first();

    $crawler = $this->client->request(
            'POST'
            , '/ajax/SET_ELEMENT_AGREEMENT/' . $validationBasketElement->getId() . '/'
    );
    $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

    $crawler = $this->client->request(
            'POST'
            , '/ajax/SET_ELEMENT_AGREEMENT/' . $validationBasketElement->getId() . '/'
            , array('agreement' => 1)
    );

    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasketElement->getId()));
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));

    $datas = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($datas), 'asserting good json datas');
    $this->assertObjectHasAttribute('datas', $datas);
    $this->assertObjectHasAttribute('error', $datas);
  }

  public function testAjaxSetRelease()
  {
    $basket = $this->insertOneBasket();

    $crawler = $this->client->request('POST', '/ajax/SET_RELEASE/' . $basket->getId() . '/');
    $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

    $validationBasket = $this->insertOneValidationBasket();

    $crawler = $this->client->request('POST', '/ajax/SET_RELEASE/' . $validationBasket->getId() . '/');

    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), sprintf('set note to element %s ', $validationBasket->getId()));
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));

    $datas = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($datas), 'asserting good json datas');
    $this->assertObjectHasAttribute('datas', $datas);
    $this->assertObjectHasAttribute('error', $datas);
  }

}
