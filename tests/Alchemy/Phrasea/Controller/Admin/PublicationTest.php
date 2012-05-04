<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

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

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';
  }

  public function testList()
  {
    $crawler = $this->client->request('GET', '/publications/list/');
    $pageContent = $this->client->getResponse()->getContent();
    $this->assertTrue($this->client->getResponse()->isOk());
    $feeds = Feed_Collection::load_all(appbox::get_instance(\bootstrap::getCore()), self::$user);

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
    $appbox = appbox::get_instance(\bootstrap::getCore());

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

    $feeds = Feed_Collection::load_all(appbox::get_instance(\bootstrap::getCore()), self::$user);
    $count_after = sizeof($feeds->get_feeds());
    $this->assertGreaterThan($count, $count_after);

    $feed = array_pop($feeds->get_feeds());

    $feed->delete();
  }

  public function testGetFeed()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');
    $crawler = $this->client->request('GET', '/publications/feed/' . $feed->get_id() . '/');
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/update/']")->count());
    $this->assertEquals(1, $crawler->filterXPath("//input[@value='salut']")->count());
    $this->assertEquals(1, $crawler->filterXPath("//input[@value='coucou']")->count());

    $feed->delete();
  }

  public function testUpdateFeedNotOwner()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    //is not owner
    $stub = $this->getMock("user_adapter", array(), array(), "", false);
    //return a different userid
    $stub->expects($this->any())->method("get_id")->will($this->returnValue(99999999));

    $feed = Feed_Adapter::create($appbox, $stub, "salut", 'coucou');
    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/update/");
    $this->assertTrue($this->client->getResponse()->isRedirect(), 'update fails, i\'m redirected');
    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/feed/' . $feed->get_id() . '/?'
            ) === 0);
    $feed->delete();
  }

  public function testUpdatedFeedException()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/update/", array(
        'title' => 'test'
        , 'subtitle' => 'test'
        , 'public' => '1'
    ));

    $feed = new Feed_Adapter($appbox, $feed->get_id());

    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/list/'
            ) === 0);

    $this->assertEquals('test', $feed->get_title());
    $this->assertEquals('test', $feed->get_subtitle());
    $this->assertTrue($feed->is_public());
    $this->assertNull($feed->get_collection());

    $feed->delete();
  }

  public function testUpdatedFeedOwner()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/update/", array(
        'title' => 'test'
        , 'subtitle' => 'test'
        , 'public' => '1'
        , 'base_id' => self::$collection->get_base_id()
    ));

    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/list/'
            ) === 0);


    $feed = new Feed_Adapter($appbox, $feed->get_id());

    $collection = $feed->get_collection();

    $this->assertEquals('test', $feed->get_title());
    $this->assertEquals('test', $feed->get_subtitle());
    $this->assertFalse($feed->is_public());
    $this->assertEquals(self::$collection->get_base_id(), $collection->get_base_id());

    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/list/'
            ) === 0);

    $feed->delete();
  }

  public function testIconUploadErrorOwner()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    //is not owner
    $stub = $this->getMock("user_adapter", array(), array(), "", false);
    //return a different userid
    $stub->expects($this->any())->method("get_id")->will($this->returnValue(99999999));


    $feed = Feed_Adapter::create($appbox, $stub, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/iconupload/", array(), array());

    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());
    $this->assertRegexp("/ERROR:you are not allowed/", $response->getContent());

    $feed->delete();
  }

  public function testIconUploadErrorFileData()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request(
            "POST"
            , "/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error' => 1))
    );
    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());
    $this->assertRegexp("/ERROR:error while upload/", $response->getContent());

    $feed->delete();
  }

  public function testIconUploadErrorFileType()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request(
            "POST"
            , "/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error' => 0, 'tmp_name' => __DIR__ . '/../../../../testfiles/test007.ppt'))
    );
    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());
    $this->assertRegexp("/ERROR:bad filetype/", $response->getContent());

    $feed->delete();
  }

  public function testIconUpload()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    copy(__DIR__ . '/../../../../testfiles/logocoll.gif', __DIR__ . '/../../../../testfiles/logocoll1.gif');
    $this->client->request(
            "POST"
            , "/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error' => 0, 'tmp_name' => __DIR__ . '/../../../../testfiles/logocoll1.gif'))
    );
    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());

    $feed = new Feed_Adapter($appbox, $feed->get_id());
    try
    {
      $file = new SplFileObject(__DIR__ . '/../../../../testfiles/logocoll1.gif');
      $this->fail('logo not deleted');
    }
    catch (\Exception $e)
    {

    }

    $this->assertRegexp("#FILEHREF:/custom/feed_" . $feed->get_id() . ".jpg?[0-9]*#", $response->getContent());

    $feed->delete();
  }

  public function testAddPublisher()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/addpublisher/", array(
        'usr_id' => self::$user_alt1->get_id()
    ));

    $response = $this->client->getResponse();
    $this->assertTrue($response->isRedirect());

    $feed = new Feed_Adapter($appbox, $feed->get_id());
    $publishers = $feed->get_publishers();

    $this->assertTrue(isset($publishers[self::$user_alt1->get_id()]));
    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/feed/' . $feed->get_id() . '/'
            ) === 0);

    $feed->delete();
  }

  public function testAddPublisherException()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/addpublisher/");

    $feed = new Feed_Adapter($appbox, $feed->get_id());
    $response = $this->client->getResponse();
    $this->assertTrue($response->isRedirect());
    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/feed/' . $feed->get_id() . '/?err'
            ) === 0);

    $feed->delete();
  }

  public function testRemovePublisher()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/removepublisher/", array(
        'usr_id' => self::$user_alt1->get_id()
    ));

    $response = $this->client->getResponse();
    $this->assertTrue($response->isRedirect());

    $feed = new Feed_Adapter($appbox, $feed->get_id());
    $publishers = $feed->get_publishers();

    $this->assertFalse(isset($publishers[self::$user_alt1->get_id()]));
    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/feed/' . $feed->get_id() . '/'
            ) === 0);

    $feed->delete();
  }

  public function testRemovePublisherException()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/removepublisher/");

    $response = $this->client->getResponse();
    $this->assertTrue($response->isRedirect());

    $feed = new Feed_Adapter($appbox, $feed->get_id());

    $this->assertTrue(
            strpos(
                    $this->client->getResponse()->headers->get('Location')
                    , '/admin/publications/feed/' . $feed->get_id() . '/?err'
            ) === 0);

    $feed->delete();
  }

  public function testDeleteFeed()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());

    $feed = Feed_Adapter::create($appbox, self::$user, "salut", 'coucou');

    $this->client->request("POST", "/publications/feed/" . $feed->get_id() . "/delete/");

    $response = $this->client->getResponse();
    $this->assertTrue($response->isRedirect());

    try
    {
      $feed = new Feed_Adapter($appbox, $feed->get_id());
      $feed->delete();
      $this->fail("fail deleting feed");
    }
    catch(\Exception $e)
    {

    }

  }

}
