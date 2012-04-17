<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_CollectionTest extends PhraseanetPHPUnitAuthenticatedAbstract
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
    self::$object->set_public(true);
  }

  public static function tearDownAfterClass()
  {
    self::$object->delete();
  }

  public function testLoad_all()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $coll = Feed_Collection::load_all($appbox, self::$user);

    foreach ($coll->get_feeds() as $feed)
    {
      $this->assertInstanceOf('Feed_Adapter', $feed);
    }
  }

  public function testGet_feeds()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $coll = Feed_Collection::load_public_feeds($appbox);

    foreach ($coll->get_feeds() as $feed)
    {
      $this->assertInstanceOf('Feed_Adapter', $feed);
    }
  }

  public function testGet_aggregate()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $coll = Feed_Collection::load_public_feeds($appbox);

    $this->assertInstanceOf('Feed_Aggregate', $coll->get_aggregate());
  }

  public function testLoad_public_feeds()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $coll = Feed_Collection::load_public_feeds($appbox);

    foreach ($coll->get_feeds() as $feed)
    {
      $this->assertTrue($feed->is_public());
    }
  }

}

