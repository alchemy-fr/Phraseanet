<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_AdapterTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  /**
   *
   * @var Feed_Adapter
   */
  protected static $object;
  protected static $title = 'Feed test';
  protected static $subtitle = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?';

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $auth = new Session_Authentication_None(self::$user);
    $appbox->get_session()->authenticate($auth);
    self::$object = Feed_Adapter::create($appbox, self::$user, self::$title, self::$subtitle);
  }

  public static function tearDownAfterClass()
  {
    self::$object->delete();
    parent::tearDownAfterClass();
  }

  public function testGet_icon_url()
  {
    $this->assertEquals('/skins/icons/rss32.gif', self::$object->get_icon_url());
  }

  public function testSet_icon()
  {
    $this->assertEquals('/skins/icons/rss32.gif', self::$object->get_icon_url());
    $file = new system_file(__DIR__ . '/../testfiles/p4logo.jpg');
    self::$object->set_icon($file);
    try
    {
      $file = new system_file(__DIR__ . '/../testfiles/iphone_pic.jpg');
      self::$object->set_icon($file);
      $this->fail('Should fail');
    }
    catch (Exception $e)
    {

    }
    $this->assertEquals('/custom/feed_' . self::$object->get_id() . '.jpg', self::$object->get_icon_url());
  }

  public function testIs_aggregated()
  {
    $this->assertFalse(self::$object->is_aggregated());
  }

  public function testIs_owner()
  {
    $this->assertTrue(self::$object->is_owner(self::$user));
  }

  public function testIs_publisher()
  {
    $this->assertTrue(self::$object->is_publisher(self::$user));
  }

  public function testIs_public()
  {
    $this->assertFalse(self::$object->is_public());
    self::$object->set_public(true);
    $this->assertTrue(self::$object->is_public());

    $coll = null;
    $appbox = appbox::get_instance(\bootstrap::getCore());
    foreach ($appbox->get_databoxes() as $databox)
    {
      foreach ($databox->get_collections() as $collection)
      {
        $coll = $collection;
        break;
      }
      if ($coll instanceof collection)
        break;
    }

    self::$object->set_collection($coll);
    $this->assertFalse(self::$object->is_public());
    self::$object->set_collection(null);
    $this->assertTrue(self::$object->is_public());
    self::$object->set_public(false);
    $this->assertFalse(self::$object->is_public());
  }

  public function testGet_publishers()
  {
    $publishers = self::$object->get_publishers();
    $this->assertEquals(1, count($publishers));
  }

  public function testGet_collection()
  {
    $this->assertNull(self::$object->get_collection());
  }

  public function testAdd_publisher()
  {
    self::$object->add_publisher(self::$user);
    $publishers = self::$object->get_publishers();
    $this->assertEquals(1, count($publishers));
  }

  public function testGet_id()
  {
    $this->assertTrue(is_int(self::$object->get_id()));
    $this->assertTrue(self::$object->get_id() > 0);
  }

  public function testSet_collection()
  {

    $coll = null;
    $appbox = appbox::get_instance(\bootstrap::getCore());
    foreach ($appbox->get_databoxes() as $databox)
    {
      foreach ($databox->get_collections() as $collection)
      {
        $coll = $collection;
        break;
      }
      if ($coll instanceof collection)
        break;
    }

    self::$object->set_collection($coll);
    $this->assertInstanceOf('collection', self::$object->get_collection());
    self::$object->set_collection(null);
    $this->assertNull(self::$object->get_collection());
  }

  public function testSet_public()
  {
    self::$object->set_collection(null);
    self::$object->set_public(true);
    $this->assertTrue(self::$object->is_public());
    self::$object->set_public(false);
    $this->assertFalse(self::$object->is_public());
  }

  public function testSet_title()
  {
    $this->assertEquals(self::$title, self::$object->get_title());
    $title = 'GROS NIchONS';
    self::$object->set_title($title);
    $this->assertEquals($title, self::$object->get_title());
    $title = '<i>GROS NIchONS</i>';
    self::$object->set_title($title);
    $this->assertNotEquals($title, self::$object->get_title());
    $this->assertEquals(strip_tags($title), self::$object->get_title());

    try
    {
      self::$object->set_title('<a></a> ');
      $this->fail();
    }
    catch (Exception $e)
    {

    }
    try
    {
      self::$object->set_title('   ');
      $this->fail();
    }
    catch (Exception $e)
    {

    }
    try
    {
      self::$object->set_title('');
      $this->fail();
    }
    catch (Exception $e)
    {

    }

    self::$object->set_title(self::$title);
  }

  public function testSet_subtitle()
  {
    $this->assertEquals(self::$subtitle, self::$object->get_subtitle());
    self::$object->set_subtitle('');

    $this->assertEquals('', self::$object->get_subtitle());
    self::$object->set_subtitle(' un sous titre ');
    $this->assertEquals(' un sous titre ', self::$object->get_subtitle());
    self::$object->set_subtitle(self::$subtitle);
    $this->assertEquals(self::$subtitle, self::$object->get_subtitle());
  }

  public function testLoad_with_user()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $this->assertEquals(Feed_Adapter::load_with_user($appbox, self::$user, self::$object->get_id())->get_id(), self::$object->get_id());
  }

  public function testGet_count_total_entries()
  {
    $n = self::$object->get_count_total_entries();
    $this->assertTrue($n === 0);
  }

  public function testGet_entries()
  {
    $entries_coll = self::$object->get_entries(0, 5);

    $this->assertInstanceOf('Feed_Entry_Collection', $entries_coll);
    $this->assertEquals(0, count($entries_coll->get_entries()));
  }

  public function testGet_homepage_link()
  {
    self::$object->set_public(false);
    $registry = registry::get_instance();
    $link = self::$object->get_homepage_link($registry, Feed_Adapter::FORMAT_ATOM);
    $this->assertNull($link);

    self::$object->set_public(true);
    $link = self::$object->get_homepage_link($registry, Feed_Adapter::FORMAT_ATOM);
    $this->assertInstanceOf('Feed_Link', $link);
  }

  public function testGet_user_link()
  {
    $registry = registry::get_instance();

    $link = self::$object->get_user_link($registry, self::$user, Feed_Adapter::FORMAT_ATOM);
    $supposed = '/feeds\/userfeed\/([a-zA-Z0-9]{12})\/' . self::$object->get_id() . '\/atom\//';

    $atom = $link->get_href();

    $this->assertRegExp($supposed, str_replace($registry->get('GV_ServerName'), '', $atom));
    $this->assertEquals($atom, self::$object->get_user_link($registry, self::$user, Feed_Adapter::FORMAT_ATOM)->get_href());
    $this->assertEquals($atom, self::$object->get_user_link($registry, self::$user, Feed_Adapter::FORMAT_ATOM)->get_href());

    $this->assertNotEquals($atom, self::$object->get_user_link($registry, self::$user, Feed_Adapter::FORMAT_ATOM, null, true)->get_href());

    $link = self::$object->get_user_link($registry, self::$user, Feed_Adapter::FORMAT_RSS);
    $supposed = '/feeds\/userfeed\/([a-zA-Z0-9]{12})\/' . self::$object->get_id() . '\/rss\//';
    $this->assertRegExp($supposed, str_replace($registry->get('GV_ServerName'), '', $link->get_href()));
  }

  public function testGet_title()
  {
    $this->assertEquals(self::$object->get_title(), self::$title);
    try
    {
      self::$object->set_title('');
      $this->fail();
    }
    catch (Exception $e)
    {

    }
  }

  public function testGet_subtitle()
  {
    $this->assertEquals(self::$object->get_subtitle(), self::$subtitle);
    self::$object->set_subtitle('');
    $this->assertEquals(self::$object->get_subtitle(), '');
    self::$object->set_subtitle(self::$subtitle);
  }

  public function testGet_created_on()
  {
    $this->assertInstanceOf('DateTime', self::$object->get_created_on());
  }

  public function testGet_updated_on()
  {
    $this->assertInstanceOf('DateTime', self::$object->get_updated_on());
  }

}

