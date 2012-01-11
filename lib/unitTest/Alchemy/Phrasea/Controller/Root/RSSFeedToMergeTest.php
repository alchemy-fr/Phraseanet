<?php

require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/Root/RSSFeeds.php';

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

use Symfony\Component\HttpFoundation\Response;

class Feed_rssfeedsTest extends PhraseanetWebTestCaseAbstract
{

  /**
   *
   * @var Feed_Collection
   */
  protected static $public_feeds;
  /**
   *
   * @var Feed_Collection
   */
  protected static $private_feeds;
  /**
   *
   * @var Feed_Adapter
   */
  protected static $feed_1_private;
  protected static $feed_1_private_title = 'Feed 1 title';
  protected static $feed_1_private_subtitle = 'Feed 1 subtitle';

  protected static $feed_1_entries = array();
  protected static $feed_2_entries = array();
  protected static $feed_3_entries = array();
  protected static $feed_4_entries = array();
  /**
   *
   * @var Feed_Adapter
   */
  protected static $feed_2_private;
  protected static $feed_2_private_title = 'Feed 2 title';
  protected static $feed_2_private_subtitle = 'Feed 2 subtitle';

  /**
   *
   * @var Feed_Adapter
   */
  protected static $feed_3_public;
  protected static $feed_3_public_title = 'Feed 3 title';
  protected static $feed_3_public_subtitle = 'Feed 3 subtitle';

  /**
   *
   * @var Feed_Adapter
   */
  protected static $feed_4_public;
  protected static $feed_4_public_title = 'Feed 4 title';
  protected static $feed_4_public_subtitle = 'Feed 4 subtitle';

  protected static $need_records = true;
  protected static $need_subdefs = true;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();

    $appbox = appbox::get_instance();
    $auth = new Session_Authentication_None(self::$user);
    $appbox->get_session()->authenticate($auth);

    self::$feed_1_private = Feed_Adapter::create($appbox, self::$user, self::$feed_1_private_title, self::$feed_1_private_subtitle);
    self::$feed_1_private->set_public(false);
    self::$feed_1_private->set_icon(new system_file(__DIR__ . '/../testfiles/logocoll.gif'));

    self::$feed_2_private = Feed_Adapter::create($appbox, self::$user, self::$feed_2_private_title, self::$feed_2_private_subtitle);
    self::$feed_2_private->set_public(false);

    self::$feed_3_public = Feed_Adapter::create($appbox, self::$user, self::$feed_3_public_title, self::$feed_3_public_subtitle);
    self::$feed_3_public->set_public(true);
    self::$feed_3_public->set_icon(new system_file(__DIR__ . '/../testfiles/logocoll.gif'));

    self::$feed_4_public = Feed_Adapter::create($appbox, self::$user, self::$feed_4_public_title, self::$feed_4_public_subtitle);
    self::$feed_4_public->set_public(true);

    $publisher = array_shift(self::$feed_4_public->get_publishers());

    for($i = 1; $i != 15; $i++)
    {
      $entry = Feed_Entry_Adapter::create($appbox, self::$feed_4_public, $publisher, 'titre entry', 'soustitre entry', 'Jean-Marie Biggaro', 'author@example.com');
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_1);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_2);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_3);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_4);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_5);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_6);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_7);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_8);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_9);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_10);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_11);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_12);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_13);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_14);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_15);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_16);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_17);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_18);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_19);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_20);
      $entry = Feed_Entry_Adapter::create($appbox, self::$feed_1_private, $publisher, 'titre entry', 'soustitre entry', 'Jean-Marie Biggaro', 'author@example.com');
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_1);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_2);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_3);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_4);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_5);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_6);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_7);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_8);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_9);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_10);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_11);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_12);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_13);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_14);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_15);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_16);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_17);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_18);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_19);
      $item = Feed_Entry_Item::create($appbox, $entry, self::$record_20);

      self::$feed_4_entries[] = $entry;
    }


    self::$public_feeds = Feed_Collection::load_public_feeds($appbox);
    self::$private_feeds = Feed_Collection::load_all($appbox, self::$user);
    $appbox->get_session()->logout();
  }

  public static function tearDownAfterClass()
  {
    self::$feed_1_private->delete();
    self::$feed_2_private->delete();
    self::$feed_3_public->delete();
    self::$feed_4_public->delete();
    parent::tearDownAfterClass();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Root.php';
  }

//$app->get('/feeds/aggregated/{format}/', function($format) use ($app, $appbox, $display_feed)
  public function testPublicFeedAggregated()
  {
    $aggregate = self::$public_feeds->get_aggregate();
    $crawler = $this->client->request('GET', '/feeds/aggregated/atom/');
    $response = $this->client->getResponse();

    $this->evaluateResponse200($response);
    $this->evaluateGoodXML($response);

    $this->evaluateAtom($response);
  }

  protected function evaluateAtom(Response $response)
  {
    $dom_doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $dom_doc->loadXML($response->getContent());

    $xpath = new DOMXPath($dom_doc);
    $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

    $this->assertEquals(1, $xpath->query('/atom:feed/atom:title')->length);
    $this->assertEquals(1, $xpath->query('/atom:feed/atom:updated')->length);
    $this->assertEquals(1, $xpath->query('/atom:feed/atom:link[@rel="self"]')->length);
    $this->assertEquals(1, $xpath->query('/atom:feed/atom:id')->length);
    $this->assertEquals(1, $xpath->query('/atom:feed/atom:generator')->length);
    $this->assertEquals(1, $xpath->query('/atom:feed/atom:subtitle')->length);
  }

  protected function evaluateGoodXML(Response $response)
  {
    $dom_doc = new DOMDocument();
    $dom_doc->loadXML($response->getContent());
    $this->assertInstanceOf('DOMDocument', $dom_doc);
    $this->assertEquals($dom_doc->saveXML(), $response->getContent());
  }

  protected function evaluateResponse200(Response $response)
  {
    $this->assertEquals(200, $response->getStatusCode(), 'Test status code ');
    $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
  }

//$app->get('/feeds/feed/{id}/{format}/', function($id, $format) use ($app, $appbox, $display_feed)
  public function testPublicFeed()
  {
    $appbox = appbox::get_instance();
    $auth = new Session_Authentication_None(self::$user);
    $appbox->get_session()->authenticate($auth);

    $link = self::$feed_3_public->get_user_link($appbox->get_registry(), self::$user, Feed_Adapter::FORMAT_ATOM)->get_href();
    $link = str_replace($appbox->get_registry()->get('GV_ServerName').'feeds/', '/', $link);

    $appbox->get_session()->logout();

    $crawler = $this->client->request('GET', "/feeds".$link);
    $response = $this->client->getResponse();

    $this->evaluateResponse200($response);
    $this->evaluateGoodXML($response);

    $this->evaluateAtom($response);
  }

//$app->get('/feeds/userfeed/aggregated/{token}/{format}/', function($token, $format) use ($app, $appbox, $display_feed)
  public function testUserFeedAggregated()
  {
    $appbox = appbox::get_instance();
    $auth = new Session_Authentication_None(self::$user);
    $appbox->get_session()->authenticate($auth);

    $link = self::$private_feeds->get_aggregate()->get_user_link($appbox->get_registry(), self::$user, Feed_Adapter::FORMAT_ATOM)->get_href();
    $link = str_replace($appbox->get_registry()->get('GV_ServerName').'feeds/', '/', $link);

    $appbox->get_session()->logout();

    $crawler = $this->client->request('GET', "/feeds".$link);
    $response = $this->client->getResponse();

    $this->evaluateResponse200($response);
    $this->evaluateGoodXML($response);

    $this->evaluateAtom($response);
  }

//$app->get('/feeds/userfeed/{token}/{id}/{format}/', function($token, $id, $format) use ($app, $appbox, $display_feed)
  public function testUserFeed()
  {
    $appbox = appbox::get_instance();
    $auth = new Session_Authentication_None(self::$user);
    $appbox->get_session()->authenticate($auth);

    $link = self::$feed_1_private->get_user_link($appbox->get_registry(), self::$user, Feed_Adapter::FORMAT_ATOM)->get_href();
    $link = str_replace($appbox->get_registry()->get('GV_ServerName').'feeds/', '/', $link);

    $appbox->get_session()->logout();

    $crawler = $this->client->request('GET', "/feeds".$link);
    $response = $this->client->getResponse();

    $this->evaluateResponse200($response);
    $this->evaluateGoodXML($response);

    $this->evaluateAtom($response);
  }

}
