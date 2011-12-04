<?php

require_once dirname(__FILE__) . '/../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class Module_LightboxTest extends PhraseanetWebTestCaseAuthenticatedAbstract
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
    $basket = basket_adapter::create($appbox, 'bask validation', self::$user);
    $basket->push_element(self::$record_1, false, false);
    $basket->validation_to_users(self::$user, true, true, true);
    $this->validation_basket = $basket;
  }

  public function tearDown()
  {
    $this->feed->delete();
//    $this->validation_basket->delete();
    parent::tearDown();
  }

  public function createApplication()
  {
    return require dirname(__FILE__) . '/../../classes/module/Lightbox.php';
  }

  /**
   *
   * @return array
   */
  protected function get_baskets()
  {
    $appbox = appbox::get_instance();
    $basketcoll = new basketCollection($appbox, self::$user->get_id());
    $basket_coll = $basketcoll->get_baskets();

    return $basket_coll['baskets'];
  }

  /**
   *
   * @return basket_adapter
   */
  protected function get_basket()
  {
    $appbox = appbox::get_instance();
    $basketcoll = new basketCollection($appbox, self::$user->get_id());
    $basket_coll = $basketcoll->get_baskets();
    while(($basket = array_shift($basket_coll['baskets'])))
    {
      if(!$basket->is_valid())

        return $basket;
    }
    $this->fail('Unable to find a basket');
  }
  protected function get_validation_basket()
  {
    return $this->validation_basket;
  }

  /**
   *
   * @return basket_element_adapter
   */
  protected function get_basket_element()
  {
    $basket = $this->get_basket();
    $basket->push_element(self::$record_1, false, false);

    foreach($basket->get_elements() as $basket_element)
    {
      return $basket_element;
    }
  }
  /**
   *
   * @return basket_element_adapter
   */
  protected function get_validation_basket_element()
  {
    $basket = $this->get_validation_basket();

    foreach($basket->get_elements() as $basket_element)
    {
      return $basket_element;
    }
  }

  public function testRouteSlash()
  {
    $baskets = $this->get_baskets();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
    $this->assertEquals($crawler->filter('div.basket_wrapper')->count(), count($baskets));

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
    $basket_element = $this->get_basket_element();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/ajax/NOTE_FORM/'.$basket_element->get_sselcont_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('', trim($this->client->getResponse()->getContent()));

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/ajax/NOTE_FORM/'.$basket_element->get_sselcont_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('', trim($this->client->getResponse()->getContent()));

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/ajax/NOTE_FORM/'.$basket_element->get_sselcont_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertNotEquals('', trim($this->client->getResponse()->getContent()));
  }

  public function testAjaxElement()
  {
    $basket_element = $this->get_basket_element();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/ajax/LOAD_BASKET_ELEMENT/'.$basket_element->get_sselcont_id().'/');
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

    $crawler = $this->client->request('GET', '/ajax/LOAD_BASKET_ELEMENT/'.$basket_element->get_sselcont_id().'/');
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

    $crawler = $this->client->request('GET', '/ajax/LOAD_BASKET_ELEMENT/'.$basket_element->get_sselcont_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertNotEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
  }

  public function testAjaxFeedItem()
  {
    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/ajax/LOAD_FEED_ITEM/'.$this->entry->get_id().'/'.$this->item->get_id().'/');
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

    $crawler = $this->client->request('GET', '/ajax/LOAD_FEED_ITEM/'.$this->entry->get_id().'/'.$this->item->get_id().'/');
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

    $crawler = $this->client->request('GET', '/ajax/LOAD_FEED_ITEM/'.$this->entry->get_id().'/'.$this->item->get_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertNotEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));
  }

  public function testValidate()
  {

    $basket = $this->get_basket();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/validate/'.$basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/validate/'.$basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/validate/'.$basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testCompare()
  {
    $basket = $this->get_basket();

    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/compare/'.$basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/compare/'.$basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/compare/'.$basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testFeedEntry()
  {
    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);

    $crawler = $this->client->request('GET', '/feeds/entry/'.$this->entry->get_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IE6);

    $crawler = $this->client->request('GET', '/feeds/entry/'.$this->entry->get_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());

    $this->set_user_agent(self::USER_AGENT_IPHONE);

    $crawler = $this->client->request('GET', '/feeds/entry/'.$this->entry->get_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }

  public function testAjaxReport()
  {
    $this->set_user_agent(self::USER_AGENT_FIREFOX8MAC);
    $crawler = $this->client->request('GET', '/ajax/LOAD_REPORT/'.$this->validation_basket->get_ssel_id().'/');
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    $this->assertEquals('UTF-8', $this->client->getResponse()->getCharset());
  }
  public function testAjaxSetNote()
  {
    $crawler = $this->client->request('POST', '/ajax/SET_NOTE/'.$this->get_basket_element()->get_sselcont_id().'/');
    $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

    $crawler = $this->client->request('POST', '/ajax/SET_NOTE/'.$this->get_validation_basket_element()->get_sselcont_id().'/');

    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), sprintf('set note to element %s ',$this->get_validation_basket_element()->get_sselcont_id()));
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));

    $datas = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($datas), 'asserting good json datas');
    $this->assertObjectHasAttribute('datas', $datas);
    $this->assertObjectHasAttribute('error', $datas);

  }
  public function testAjaxSetAgreement()
  {
    $crawler = $this->client->request('POST', '/ajax/SET_ELEMENT_AGREEMENT/'.$this->get_basket_element()->get_sselcont_id().'/');
    $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

    $crawler = $this->client->request('POST', '/ajax/SET_ELEMENT_AGREEMENT/'.$this->get_validation_basket_element()->get_sselcont_id().'/');

    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), sprintf('set note to element %s ',$this->get_validation_basket_element()->get_sselcont_id()));
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));

    $datas = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($datas), 'asserting good json datas');
    $this->assertObjectHasAttribute('datas', $datas);
    $this->assertObjectHasAttribute('error', $datas);
  }
  public function testAjaxSetRelease()
  {
    $crawler = $this->client->request('POST', '/ajax/SET_RELEASE/'.$this->get_basket()->get_ssel_id().'/');
    $this->assertEquals(400, $this->client->getResponse()->getStatusCode());

    $crawler = $this->client->request('POST', '/ajax/SET_RELEASE/'.$this->get_validation_basket()->get_ssel_id().'/');

    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), sprintf('set note to element %s ',$this->get_validation_basket()->get_ssel_id()));
    $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-type'));

    $datas = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($datas), 'asserting good json datas');
    $this->assertObjectHasAttribute('datas', $datas);
    $this->assertObjectHasAttribute('error', $datas);
  }

}
