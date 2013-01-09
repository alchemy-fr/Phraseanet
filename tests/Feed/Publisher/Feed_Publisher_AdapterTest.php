<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_Publisher_AdapterTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Feed_Publisher_Adapter
     */
    protected static $object;
    protected static $title = 'Feed test';
    protected static $subtitle = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?';

    /**
     * @var Feed_Adapter
     */
    protected static $feed;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        
        self::$feed = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], self::$title, self::$subtitle);
        $publishers = self::$feed->get_publishers();
        self::$object = array_pop($publishers);
    }

    public static function tearDownAfterClass()
    {
        self::$feed->delete();
        parent::tearDownAfterClass();
    }

    public function testGet_user()
    {
        $this->assertInstanceOf('user_Adapter', self::$object->get_user());
        $this->assertEquals(self::$DI['user']->get_id(), self::$object->get_user()->get_id());
    }

    public function testIs_owner()
    {
        $this->assertTrue(self::$object->is_owner());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_created_on());
    }

    public function testGet_added_by()
    {
        $this->assertInstanceOf('User_Adapter', self::$object->get_added_by());
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int(self::$object->get_id()));
    }
}
