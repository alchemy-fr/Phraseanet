<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/Admin/Publications.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class Module_Admin_Route_PublicationTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  public static $account = null;
  public static $api = null;
  protected $client;

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
  }

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function tearDown()
  {
    parent::tearDown();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Admin.php';
  }

  public function testList()
  {
    $crawler = $this->client->request('GET', '/publications/list/');
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());
    $feeds = Feed_Collection::load_all(appbox::get_instance(), self::$user);
    $this->assertEquals(sizeof($feeds->get_feeds()), $crawler->filterXPath("//table/tbody/tr")->count());
    foreach ($feeds->get_feeds() as $feed)
    {
      $this->assertRegExp('/\/admin\/publications\/feed\/' . $feed->get_id() . '/', $pageContent);
      if ($feed->get_collection() != null)
        $this->assertRegExp('/' . $feed->get_collection()->get_name() . '/', $pageContent);
      if ($feed->is_owner(self::$user))
        $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/delete/']")->count());
    }
  }

  public function testCreate()
  {
    $appbox = appbox::get_instance();

    foreach ($appbox->get_databoxes() as $databox)
    {
      foreach ($databox->get_collections() as $collection)
      {
        $base_id = $collection->get_base_id();
        break;
      }
    }
    $feeds = Feed_Collection::load_all($appbox, self::$user);
    $count = sizeof($feeds->get_feeds());

    $crawler = $this->client->request('POST', '/publications/create/', array("title" => "hello", "subtitle" => "coucou", "base_id" => $base_id));

    $this->assertTrue($this->client->getResponse()->isRedirect('/admin/publications/list/'));

    $feeds = Feed_Collection::load_all(appbox::get_instance(), self::$user);
    $count_after = sizeof($feeds->get_feeds());
    $this->assertGreaterThan($count, $count_after);
  }

  public function testGetFeed()
  {
    $appbox = appbox::get_instance();
    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');
    $crawler = $this->client->request('GET', '/publications/feed/'.$feed->get_id().'/');
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/update/']")->count());
    $this->assertEquals(1, $crawler->filterXPath("//input[@value='salut']")->count());
    $this->assertEquals(1, $crawler->filterXPath("//input[@value='coucou']")->count());

    $feed->delete();
  }

  public function testUpdateFeed()
  {
    $appbox = appbox::get_instance();
    //is not owner
    $stub = $this->getMock("user_adapter", array(), array(), "", false);
    //return a different userid
    $stub->expects($this->any())->method("get_id")->will($this->returnValue(99999999));

    $feed = Feed_Adapter::create($appbox, $stub, "salut", 'coucou');
    $crawler = $this->client->request("POST", "/publications/feed/".$feed->get_id()."/update/");
    $this->assertTrue($this->client->getResponse()->isRedirect(), 'update fails, i\'m redirected');
    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/feed/'.$feed->get_id().'/?'
                    ) === 0);
    $feed->delete();
  }
}
